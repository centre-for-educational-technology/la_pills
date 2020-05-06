<?php

namespace Drupal\la_pills_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Entity\User;

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
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    $instance->dateFormatter = $container->get('date.formatter');
    return $instance;
  }

  /**
   * Paginated action list.
   *
   * @return array
   *   Page renderable.
   */
  public function list() {
    $query = $this->database->select('la_pills_analytics_action', 'a');
    $query->fields('a');
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

    $response['actions'] = [
      '#type' => 'container',
    ];
    $response['actions']['actions'] = [
      '#type' => 'table',
      '#caption' => $this->t('Actions (@count)', [
        '@count' => $this->database->select('la_pills_analytics_action', 'a')->countQuery()->execute()->fetchField(),
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
