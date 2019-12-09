<?php

namespace Drupal\la_pills_timer\Controller;

use Drupal\Core\Session\AccountProxy;
use Drupal\Core\Ajax\AjaxResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Link;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Symfony\Component\HttpFoundation\Response;
use Drupal\la_pills\Entity\SessionEntity;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\la_pills_timer\Entity\LaPillsTimerEntity;
use Drupal\la_pills_timer\Entity\LaPillsTimerEntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\la_pills_timer\Entity\LaPillsSessionTimerEntity;
use Drupal\Core\Ajax\RedirectCommand;

/**
 * Class LaPillsTimerController.
 */
class LaPillsTimerController extends ControllerBase {

  /**
   * @inheritdoc
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxy $currentUser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Returns all identifiers for all timers that belong to current user and are
   * part of a certain group. Identifiers are sorted by creation date.
   *
   * @param  stirng $group
   *   Group identifier
   * @return array
   *   An array of identifiers
   */
  private function getUserTimerIdsForGroup(string $group) : array {
    return \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id())
      ->condition('group', $group)
      ->sort('created', 'DESC')
      ->execute();
  }

  /**
   * Returns all identifiers for all session timers that belong to a session and
   * are part of a certain group. Identifiers are sorted by creation date.
   *
   * @param  SessionEntity $entity
   *   Session entity instance
   * @param  string        $group
   *   Group identifier
   * @return array
   *   An array of identifiers
   */
  private function getSessionEntityIdsForGroup(SessionEntity $entity, string $group) : array {
    return \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $entity->id())
      ->condition('group', $group)
      ->sort('created', 'DESC')
      ->execute();
  }

  /**
   * Get elements for output.
   *
   * @param  array  $tids
   *   Array of identifiers
   * @param  string $entity_type
   *   Entity type
   * @return array
   *   An array of objects
   */
  private function getElements(array $tids, string $entity_type = 'la_pills_timer_entity') : array {
    $timers = $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadMultiple($tids);

    $elements = $this->entityTypeManager
      ->getViewBuilder($entity_type)
      ->viewMultiple($timers);

    return $elements;
  }

  /**
   * Page with all user timers.
   */
  public function index() {
    $options = [
      'attributes' => [
        'class' =>['use-ajax', 'btn', 'btn-success'],
        'data-dialog-type' => 'modal',
      ]
    ];

    $link = Link::createFromRoute(
      $this->t('Create new activity'),
      'la_pills_timer.la_pills_timer_controller_addTimer',
      [],
      $options);

    $data = [
      '#theme' => 'la_pills_timers',
      '#new_timer' => $link,
    ];

    foreach(['student', 'teacher', 'other'] as $group) {
      $ids = $this->getUserTimerIdsForGroup($group);

      if (!empty($ids)) {
        $elements = $this->getElements($ids);

        $data['#' . $group] = [
          '#theme' => 'la_pills_timer_elements',
          '#elements' => $elements,
        ];
      }
    }

    return $data;
  }

  /**
   * Callback for adding a new timer.
   */
  public function addTimer() {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\la_pills_timer\Form\LaPillsTimerAddForm');
    return $form;
  }

  /**
   * Callback for updating a timer.
   */
  public function updateTimer(LaPillsTimerEntity $timer) {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\la_pills_timer\Form\LaPillsTimerEditForm', $timer->id());
    return $form;
  }

  /**
   * Callback for removing a timer.
   */
  public function removeTimer(LaPillsTimerEntity $timer) {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\la_pills_timer\Form\LaPillsTimerRemoveForm', $timer->id());
    return $form;
  }

  /**
   * Callback for starting a timer.
   */
  public function sessionTimer(SessionEntity $session_entity, LaPillsSessionTimerEntity $timer) {
    $response = new AjaxResponse();

    if (!($session_entity->access('update') && $session_entity->isActive())) {
      $response->addCommand(new AlertCommand($this->t('Activity can only be logged for active data gathering sessions.')));

      return $response;
    }

    if ($timer && $timer->getSessionId() === $session_entity->id()) {
      $status = $timer->getStatus();
      $timer_id = $timer->id();

      if ($status) {
        $timer->stopSession();
        $response->addCommand(new HtmlCommand('.lapills-timer-time-' . $timer_id, '00:00:00'));
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'removeClass', ['la-pills-active-timer']));
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'countimer', ['stop']));
      } else {
        $timer->startSession();
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'addClass', ['la-pills-active-timer']));
        $response->addCommand(new HtmlCommand('.lapills-timer-time-' . $timer_id, '00:00:00'));
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'countimer', ['start']));
      }

      $timer->save();
    }

    return $response;
  }

  /**
   * Callback for stopping all timers.
   */
  public function stopAll(SessionEntity $session_entity) {
    $timer_manager = \Drupal::service('la_pills_timer.manager');
    $response = new AjaxResponse();

    if (!($session_entity->access('update') && $session_entity->isActive())) {
      $response->addCommand(new AlertCommand($this->t('Activity can only be logged for active data gathering sessions.')));

      return $response;
    }

    $stopped_timers = $timer_manager->stopAllActiveTimers($session_entity);

    if ($stopped_timers) {
      foreach ($stopped_timers as $timer) {
        $timer_id = $timer->id();
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'removeClass', ['la-pills-active-timer']));
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'countimer', ['stop']));
      }
    }

    return $response;
  }

  /**
   * Callback to generate CSV data about a timer.
   */
  public function exportTimers(SessionEntity $session_entity) {
    $request = \Drupal::request();
    $timer_manager = \Drupal::service('la_pills_timer.manager');

    if ($timer_manager->getSessionEntityActiveTimersCount($session_entity)) {
      $response = new AjaxResponse();

      $response->addCommand(new AlertCommand($this->t('Please deactivate any active logging activities and try again.')));

      return $response;
    } else if ($request->isXmlHttpRequest()) {
      $response = new AjaxResponse();

      $response->addCommand(new RedirectCommand($request->getUri()));

      return $response;
    }

    $timers = $timer_manager->getSessionEntityTimers($session_entity);

    if ($timers) {

      $handle = fopen('php://temp', 'wb');
      fputcsv($handle, [$this->t('Name'), $this->t('Group'), $this->t('Started'), $this->t('Finished'), $this->t('Duration'), $this->t('Total'),]);

      foreach ($timers as $timer) {
        if ($timer) {
          $status = $timer->getStatus();

          // This check does not make much sense as export is only allowed if no active timers re present
          if (!$status) {
            $sessions_ids = $timer->getSessionsIds();

            $timer_sessions = $this->entityTypeManager()
              ->getStorage('la_pills_timer_session_entity')
              ->loadMultiple($sessions_ids);

            if ($timer_sessions) {
              $total = 0;

              foreach ($timer_sessions as $timer_session) {
                $duration = $timer_session->getDuration();
                $total += $duration;

                fputcsv($handle, [
                  $timer->getName(),
                  $timer->get('group')->value,
                  date('d-m-Y h:m:s', $timer_session->getStartTime()),
                  date('d-m-Y h:m:s', $timer_session->getStopTime()),
                  gmdate('H:i:s', $duration), // TODO Might need a fix to handle periods longer than one day
                  gmdate('H:i:s', $total), // TODO Might need a fix to handle periods longer than one day
                ]);
              }
            }
          }
        }
      }

      rewind($handle);

      $response = new Response(stream_get_contents($handle));
      fclose($handle);

      $response->headers->set('Content-Type', 'text/csv');
      $response->headers->set('Content-Disposition','attachment; filename="data_gathering_session_activity_log.csv"');
    }

    return $response;
  }

  /**
   * Sesson entity activity logging page
   *
   * @param  SessionEntity $session_entity
   *   Session entity object
   * @return mixed
   *   An array with page structure or HTTP_FORBIDDEN response
   */
  public function sessionEntityTimers(SessionEntity $session_entity) {
    $timer_manager = \Drupal::service('la_pills_timer.manager');

    if ($timer_manager->getSessionEntityTimersCount($session_entity) === 0) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }

    $stop_link = Link::createFromRoute(
      $this->t('Stop all logging'),
      'la_pills_timer.la_pills_timer_controller_stopAll',
      [
        'session_entity' => $session_entity->id(),
      ],
      [
        'attributes' => [
        'class' =>['use-ajax', 'btn'],
      ]
    ]);
    $download_link = Link::createFromRoute(
      $this->t('Download data'),
      'la_pills_timer.la_pills_timer_controller_exportTimers',
      [
        'session_entity' => $session_entity->id(),
      ],
      [
        'attributes' => [
        'class' =>['btn', 'btn-primary', 'use-ajax',],
      ]
    ]);

    $data = [
      '#theme' => 'la_pills_session_timers',
      '#stop_timers' => $stop_link,
      '#download_data' => $download_link,
    ];

    foreach(['student', 'teacher', 'other'] as $group) {
      $ids = $this->getSessionEntityIdsForGroup($session_entity, $group);

      if (!empty($ids)) {
        $elements = $this->getElements($ids, 'la_pills_session_timer_entity');

        $data['#' . $group] = [
          '#theme' => 'la_pills_session_timer_elements',
          '#elements' => $elements,
        ];
      }
    }

    return $data;
  }

  /**
   * Custom access check for session entity timers local task
   *
   * @param  AccountInterface $account
   *   Current account
   * @param  SessionEntity    $session_entity
   *   Session entity
   * @return AccessResult
   *   AccessResultAllowed or AccessResultForbidden
   */
  public function sessionEntityTimersAccess(AccountInterface $account, SessionEntity $session_entity) : AccessResult {
    $timer_manager = \Drupal::service('la_pills_timer.manager');

    if ($timer_manager->canAccessSessionEntityTimersPage($session_entity)) {
      return AccessResult::allowed();
    }

    return AccessResult::forbidden();
  }

  /**
   * Returns renderable for active or inactive checkbox.
   *
   * @param  LaPillsTimerEntityInterface $timer
   *   Timer entity
   * @return array
   *   An array with renderable structure
   */
  public static function activeRenderable(LaPillsTimerEntityInterface $timer) {
    $renderable = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'timer-' . $timer->id() . '-active-inactive-wrapper',
      ],
    ];
    $renderable['active'] = [
      '#type' => 'checkbox',
      '#checked' => $timer->getStatus() ? TRUE : FALSE,
      '#attributes' => [
        'title' => t('Mark timer as active'),
        'data-toggle' => 'tooltip',
        'class' => ['timer-active-inactive'],
        'data-id' => $timer->id(),
      ],
    ];

    return $renderable;
  }

  /**
   * AJAX callback to make a timer active or inactive.
   *
   * @param  LaPillsTimerEntityInterface $timer
   *   Timer entity
   * @return AjaxResponse
   *   AjaxRespone with actions
   */
  public function ajaxTimerActiveInactive(LaPillsTimerEntityInterface $timer) {
    $response = new AjaxResponse();

    if ($timer->getStatus()) {
      $timer->set('status', FALSE);
    } else {
      $timer->set('status', TRUE);
    }

    $timer->save();

    $renderable = $this->activeRenderable($timer);

    $response->addCommand(
      new ReplaceCommand(
        '#timer-' . $timer->id() . '-active-inactive-wrapper',
        render($renderable)
      )
    );

    return $response;
  }

}
