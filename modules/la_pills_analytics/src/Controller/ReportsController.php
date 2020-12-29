<?php

namespace Drupal\la_pills_analytics\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Drupal\Core\Url;
use Drupal\Core\Database\Query\SelectInterface;

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
   * Apply filter conditions to a query
   *
   * @param SelectInterface $query   Action select query
   * @param Request         $request Request object
   * 
   * @return void
   */
  private function applyConditions(SelectInterface &$query, Request &$request) : void {
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
  }

  /**
   * Paginated action list.
   *
   * @return array
   *   Page renderable.
   */
  public function list(Request $request) {
    $session = $request->getSession();

    if ($session->has('la_pills_analytics_report_path')) {
      $response['download'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['download-report'],
        ],
        '#attached' => [
          'library' => [
            'la_pills_analytics/download_report',
          ],
        ],
      ];
    }

    $query = $this->database->select('la_pills_analytics_action', 'a');
    $query->fields('a');

    $this->applyConditions($query, $request);

    $count_query = clone $query;
    $count = $count_query->countQuery()->execute()->fetchField();

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
        '@count' => $count,
      ]),
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No actions found'),
      '#sticky' => TRUE,
    ];

    if ($count > 0) {
      $response['actions']['download'] = [
        '#type' => 'link',
        '#title' => $this->t('Download as CSV'),
        '#url' => Url::fromRoute('la_pills_analytics.reports_controller_download', [], [
          'query' => $request->query->all(),
        ]),
        '#attributes' => [
          'class' => [
            'button', 'btn', 'btn-primary',
          ],
        ],
      ];
    }

    $response['actions']['pager'] = [
      '#type' => 'pager',
    ];

    return $response;
  }

  /**
   * Prepare or download report
   *
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Symfony\Component\HttpFoundation\RedirectResponse|null
   *   Batch process or file download after batch completion.
   */
  public function download(Request $request) {
    $session = $request->getSession();

    if ($request->get('download') && $session->has('la_pills_analytics_report_path')) {
      $headers = [
        'Content-Type' => 'text/csv',
        'Content-Description' => 'File Download',
        'Content-Disposition' => 'attachment; filename=report.csv',
      ];

      // TODO Consider removing file path from session and file itself

      return new BinaryFileResponse($session->get('la_pills_analytics_report_path'), 200, $headers, true);
    }

    $range_size = 100;
    $base_query = $this->database->select('la_pills_analytics_action', 'a');
    $base_query->fields('a');

    $this->applyConditions($base_query, $request);

    $count_query = clone $base_query;

    $count = $count_query->countQuery()->execute()->fetchField();

    $batch = [
      'title' => $this->t('Processing requested activity data'),
      'operations' => [
        [
          '\Drupal\la_pills_analytics\Batch\ReportDownload::preprocess',
          [
            $base_query,
          ]
        ]
      ],
      'finished' => '\Drupal\la_pills_analytics\Batch\ReportDownload::finished',
    ];

    foreach(range(0, floor($count / $range_size)) as $number) {
      $batch['operations'][] = [
        '\Drupal\la_pills_analytics\Batch\ReportDownload::process',
        [
          [
            'start' => $number * $range_size,
            'length' => $range_size, // TODO See if we need to determine correct length
          ]
        ],
      ];
    }

    batch_set($batch);

    return batch_process('admin/reports/analytics/list');
  }

}
