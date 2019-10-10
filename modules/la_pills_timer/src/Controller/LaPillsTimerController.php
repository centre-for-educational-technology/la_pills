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
      $this->t('Create new timer'),
      'la_pills_timer.la_pills_timer_controller_addTimer',
      [],
      $options);

    $data = [
      '#theme' => 'la_pills_timers',
      '#new_timer' => $link,
    ];

    $query_students = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id())
      ->condition('group', 'student')
      ->sort('created', 'DESC');
    $students_ids = $query_students->execute();

    if (!empty($students_ids)) {
      $student_elements = $this->getElements($students_ids);

      $data['#student'] = [
        '#theme' => 'la_pills_timer_elements',
        '#elements' => $student_elements,
      ];
    }

    $query_teacher = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id())
      ->condition('group', 'teacher')
      ->sort('created', 'DESC');
    $teacher_ids = $query_teacher->execute();

    if (!empty($teacher_ids)) {
      $teacher_elements = $this->getElements($teacher_ids);

      $data['#teacher'] = [
        '#theme' => 'la_pills_timer_elements',
        '#elements' => $teacher_elements,
      ];
    }

    $query_other = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id())
      ->condition('group', 'other')
      ->sort('created', 'DESC');
    $other_ids = $query_other->execute();

    if (!empty($other_ids)) {
      $other_elements = $this->getElements($other_ids);

      $data['#other'] = [
        '#theme' => 'la_pills_timer_elements',
        '#elements' => $other_elements,
      ];
    }

    return $data;
  }

  /**
   * Get elements for output.
   */
  private function getElements($tids, $entity_type = 'la_pills_timer_entity') {
    $timers = $this->entityTypeManager
      ->getStorage($entity_type)
      ->loadMultiple($tids);

    $elements = $this->entityTypeManager
      ->getViewBuilder($entity_type)
      ->viewMultiple($timers);

    return $elements;
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
  public function updateTimer($timer_id = NULL) {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\la_pills_timer\Form\LaPillsTimerEditForm', $timer_id);
    return $form;
  }

  /**
   * Callback for removing a timer.
   */
  public function removeTimer($timer_id = NULL) {
    $form = \Drupal::formBuilder()
      ->getForm('\Drupal\la_pills_timer\Form\LaPillsTimerRemoveForm', $timer_id);
    return $form;
  }

  /**
   * Callback for starting a timer.
   */
  public function sessionTimer(SessionEntity $session_entity, $timer_id = NULL) {
    // TODO Need to check permissions on Session Entity
    // TODO Need to report back to user if session is already closed
    $response = new AjaxResponse();
    if ($timer_id) {

      $timer = $this->entityTypeManager
        ->getStorage('la_pills_session_timer_entity')
        ->load($timer_id);
      // TODO Need to check if timer belongs to a session

      if ($timer) {
        $status = $timer->getStatus();

        if ($status) {
          $timer->stopSession();
          $response->addCommand(new HtmlCommand('.lapills-timer-time-' . $timer_id, '00:00:00'));
          $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'removeClass', ['la-pills-active-timer']));
          $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'countimer', ['stop']));
          $response->addCommand(new InvokeCommand('.la-pills-timer-' . $timer_id . ' .export-button', 'removeClass', ['hidden']));

        } else {
          $timer->startSession();
          $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'addClass', ['la-pills-active-timer']));
          $response->addCommand(new HtmlCommand('.lapills-timer-time-' . $timer_id, '00:00:00'));
          $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'countimer', ['start']));
          $response->addCommand(new InvokeCommand('.la-pills-timer-' . $timer_id . ' .export-button', 'addClass', ['hidden']));
        }

        $timer->save();
      }
    }

    return $response;
  }

  /**
   * Callback for stopping all timers.
   */
  public function stopAll(SessionEntity $session_entity) {
    $response = new AjaxResponse();

    $query = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $session_entity->id())
      ->condition('status', TRUE)
      ->sort('created', 'DESC');
    $active_timers_ids = $query->execute();

    if ($active_timers_ids) {
      $timers = $this->entityTypeManager
        ->getStorage('la_pills_session_timer_entity')
        ->loadMultiple($active_timers_ids);

      foreach ($timers as $timer) {
        $timer_id = $timer->id();
        $timer->stopSession();
        $timer->save();
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'removeClass', ['la-pills-active-timer']));
        $response->addCommand(new InvokeCommand('.lapills-timer-time-' . $timer_id, 'countimer', ['stop']));
        $response->addCommand(new InvokeCommand('.la-pills-timer-' . $timer_id . ' .export-button', 'removeClass', ['hidden']));
      }
    }

    return $response;
  }

  /**
   * Callback to generate CSV data about a timer.
   */
  public function exportTimer($timer_id = NULL) {
    throw \Exception('Needs a different implementation');
    if ($timer_id) {
      $timer = $this->entityTypeManager
        ->getStorage('la_pills_timer_entity')
        ->load($timer_id);

      if ($timer) {
        $status = $timer->getStatus();

        if (!$status) {
          $sessions_ids = $timer->getSessionsIds();

          $timer_sessions = $this->entityTypeManager()
            ->getStorage('la_pills_timer_session_entity')
            ->loadMultiple($sessions_ids);

          if ($timer_sessions) {
            $handle = fopen('php://temp', 'wb');

            fputcsv($handle, ['Name', 'Group', 'Started', 'Finished', 'Duration', 'Total']);

            $total = 0;

            foreach ($timer_sessions as $timer_session) {
              $duration = $timer_session->getDuration();
              $total += $duration;

              fputcsv($handle, [
                $timer->getName(),
                $timer->get('group')->value,
                date('d-m-Y h:m:s', $timer_session->getStartTime()),
                date('d-m-Y h:m:s', $timer_session->getStopTime()),
                gmdate('H:i:s', $duration),
                gmdate('H:i:s', $total),
              ]);
            }

            rewind($handle);

            $response = new Response(stream_get_contents($handle));
            fclose($handle);

            $response->headers->set('Content-Type', 'text/csv');
            $response->headers->set('Content-Disposition','attachment; filename="timer.csv"');
          }
        } else {
          $error = [
            '#type' => 'html_tag',
            '#tag' => 'div',
            '#value' => $this
              ->t('Please stop timer for export.'),
            '#attributes' => ['class' => ['alert alert-danger alert-dismissible']],
          ];
          $response = new AjaxResponse();

          $response->addCommand(new ReplaceCommand('.la-pills-timer-' . $timer_id . ' .message-export-timer', $error));
        }
      }
    }

    return $response;
  }

  private function createSessionTimers(SessionEntity $session_entity) {
    // TODO Check if timers have not been attached yet
    $query_timers = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id())
      ->condition('status', '1')
      ->sort('created', 'DESC');

    $timers = $this->entityTypeManager
        ->getStorage('la_pills_timer_entity')
        ->loadMultiple($query_timers->execute());

    foreach($timers as $timer) {
      $session_timer = $this->entityTypeManager()
        ->getStorage('la_pills_session_timer_entity')
        ->create([
          'session_id' => $session_entity->id(),
          'name' => $timer->getName(),
          'group' => $timer->getTimerGroup(),
          'color' => $timer->get('color')->value,
        ]);
      $session_timer->save();
    }
  }

  // TODO Make sure that this function functions as required
  public function sessionEntityTimers(SessionEntity $session_entity) {
    //$this->createSessionTimers($session_entity);
    $stop_link = Link::createFromRoute(
      $this->t('Stop all timers'),
      'la_pills_timer.la_pills_timer_controller_stopAll',
      [
        'session_entity' => $session_entity->id(),
      ],
      [
        'attributes' => [
          'class' =>['use-ajax', 'btn'],
        ]
      ]);

    $data = [
      '#theme' => 'la_pills_session_timers',
      '#stop_timers' => $stop_link,
    ];

    $query_students = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $session_entity->id())
      ->condition('group', 'student')
      ->sort('created', 'DESC');
    $students_ids = $query_students->execute();

    if (!empty($students_ids)) {
      $student_elements = $this->getElements($students_ids, 'la_pills_session_timer_entity');

      $data['#student'] = [
        '#theme' => 'la_pills_session_timer_elements',
        '#elements' => $student_elements,
      ];
    }

    $query_teacher = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $session_entity->id())
      ->condition('group', 'teacher')
      ->sort('created', 'DESC');
    $teacher_ids = $query_teacher->execute();

    if (!empty($teacher_ids)) {
      $teacher_elements = $this->getElements($teacher_ids, 'la_pills_session_timer_entity');

      $data['#teacher'] = [
        '#theme' => 'la_pills_session_timer_elements',
        '#elements' => $teacher_elements,
      ];
    }

    $query_other = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $session_entity->id())
      ->condition('group', 'other')
      ->sort('created', 'DESC');
    $other_ids = $query_other->execute();

    if (!empty($other_ids)) {
      $other_elements = $this->getElements($other_ids, 'la_pills_session_timer_entity');

      $data['#other'] = [
        '#theme' => 'la_pills_session_timer_elements',
        '#elements' => $other_elements,
      ];
    }

    return $data;
  }

}
