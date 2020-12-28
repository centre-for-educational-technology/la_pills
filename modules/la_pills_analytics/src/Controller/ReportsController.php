<?php

namespace Drupal\la_pills_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class ReportsController.
 */
class ReportsController extends ControllerBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Date formatter
   *
   * @var \Drupal\Core\Datetime\DateFormatterInterface
   */
  protected $dateFormatter;

  /**
   * Form builder
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->dateFormatter = $container->get('date.formatter');
    $instance->formBuilder = $container->get('form_builder');
    return $instance;
  }

  /**
   * Paginated action list.
   *
   * @return array
   *   Page renderable.
   */
  public function list(Request $request) {
    $query = $this->database->select('la_pills_analytics_action', 'a');
    $query->fields('a');

    if ($request->get('op')) {
      $types = $request->get('types');

      if ($types && is_array($types) && count($types) > 0) {
        $query->condition('a.type', $types, 'IN');
      }

      $sessions = $request->get('sessions');

      if ($sessions && is_array($sessions) && count($sessions) > 0) {
        $query->condition('a.session_id', $sessions, 'IN');
      }

      $users = $request->get('users');

      if ($users && is_array($users) && count($users) > 0) {
        $has_special_case = in_array(0, $users);

        if ($has_special_case && count($users) > 1) {
          $group = $query->orConditionGroup()
            ->isNull('a.user_id')
            ->condition('a.user_id', $users, 'IN');
          $query->condition($group);
        } else if ($has_special_case) {
          $query->isNull('a.user_id');
        } else if (!$has_special_case) {
          $query->condition('a.user_id', $users, 'IN');
        }
      }

      $from = $request->get('from');
      $until = $request->get('until');
      
      if ($from) {
        $query->condition('a.created', strtotime($from), '>=');
      }

      if ($until) {
        $query->condition('a.created', strtotime($until) + strtotime('1 day -1 second', 0), '<=');
      }
    }

    $count_query = clone $query;

    $query->orderBy('a.created', 'ASC');
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(50);
    $result = $pager->execute();

    $header = [
      $this->t('Type'),
      $this->t('Path'),
      $this->t('URI'),
      $this->t('Title'),
      $this->t('Session'),
      $this->t('User'),
      $this->t('Created'),
    ];
    $rows = [];

    foreach ($result->fetchAll() as $action) {
      $user = $action->user_id ? User::load($action->user_id) : NULL;

      $rows[] = [
        'data' => [
          'type' => $action->type,
          'path' => $action->path,
          'uri' => $action->uri,
          'title' => $action->title,
          'session' => $action->session_id,
          'user' => $user ? $user->toLink() : $action->name,
          'created' => $this->dateFormatter->format($action->created, 'long'),
        ],
      ];
    }

    $form = $this->formBuilder->getForm('Drupal\la_pills_analytics\Form\ReportsFilterForm');

    $response['filter-form'] = $form;
    $response['actions'] = [
      '#type' => 'container',
    ];
    $response['actions']['actions'] = [
      '#type' => 'table',
      '#caption' => $this->t('Actions (@count)', [
        '@count' => $count_query->countQuery()->execute()->fetchField(),
      ]),
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No actions found'),
      '#sticky' => TRUE,
    ];
    $response['actions']['pager'] = [
      '#type' => 'pager',
    ];

    return $response;
  }

}
