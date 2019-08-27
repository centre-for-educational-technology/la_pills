<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\la_pills\SessionTemplateManager;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Link;
use Drupal\Core\Database\Connection;
use Drupal\la_pills\FetchClass\SessionTemplate;
use Drupal\Core\Datetime\DateFormatter;

/**
 * Class SessionTemplateController.
 */
class SessionTemplateController extends ControllerBase {

  /**
   * Session Template Manager
   *
   * @var Drupal\la_pills\SessionTemplateManager
   */
  protected $sessionTemplateManager;
  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;
  /**
   * Date formatter
   *
   * @var \Drupal\Core\Datetime\DateFormatter
   */
  protected $dateFormatter;

  /**
   * Controller constructor
   *
   * @param Drupal\la_pills\SessionTemplateManager $session_template_manager
   *   Sessin Template Manager
   */
  public function __construct(SessionTemplateManager $session_template_manager, Connection $connection, DateFormatter $dateFormatter) {
    $this->sessionTemplateManager = $session_template_manager;
    $this->connection = $connection;
    $this->dateFormatter = $dateFormatter;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $session_template_manager = $container->get('la_pills.session_template_manager');
    $connection = $container->get('database');
    $dateFormatter = $container->get('date.formatter');

    return new static($session_template_manager, $connection, $dateFormatter);
  }

  /**
   * Returns Session Template preview page title
   *
   * @param  Symfony\Component\HttpFoundation\Request $request
   *   Request object
   *
   * @return string
   *  Page title
   */
  public function previewTitle(Request $request) {
    $session_template = $this->sessionTemplateManager->getTemplate($request->attributes->get('session_template'));
    $session_template_data = $session_template->getData();

    return $this->t('Session Template Preview: @title', [
      '@title' => $session_template_data['context']['title'],
    ]);
  }

  /**
   * Returns Session Template preview page structure
   *
   * @param  Symfony\Component\HttpFoundation\Request $request
   *   Request object
   *
   * @return array
   *   Content structure
   */
  public function preview(Request $request) {
    $session_template = $this->sessionTemplateManager->getTemplate($request->attributes->get('session_template'));

    if (!$session_template) {
      throw new NotFoundHttpException();
    }

    $session_template_data = $session_template->getData();

    $current_url = Url::fromRoute('session_template.preview', [
      'session_template' => $session_template->uuid,
    ], [
      'absolute' => TRUE,
    ]);

    $replacements = [
      '{{website}}' => Link::fromTextAndUrl($current_url->toString(), $current_url)->toString(),
      '{{dashboard}}' => Link::fromTextAndUrl($this->t('dashboard'), $current_url)->toString(),
      '{{Dashboard}}' => Link::fromTextAndUrl($this->t('Dashboard'), $current_url)->toString(),
    ];

    if ($session_template_data['questionnaires']) {
      foreach ($session_template_data['questionnaires'] as $questionnaire) {
        $replacements['{{' . $questionnaire['id'] . '}}'] = Link::fromTextAndUrl($questionnaire['title'], $current_url)->toString();
      }
    }

    if ($session_template->hasExternalDashboard()) {
      $response['custom-template'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => [
            'alert',
            'alert-info',
            'text-center',
          ],
        ],
      ];
      $response['custom-template']['text'] = [
        '#type' => 'html_tag',
        '#tag' => 'strong',
        '#value' => $this->t('This template has custom dashboard!'),
      ];
    }

    if (\Drupal::currentUser()->hasPermission('administer site configuration')) {
      $actions['delete'] = Link::createFromRoute(
        t('Delete template'),
        'session_template.delete',
        ['session_template' => $session_template->uuid],
        [
          'attributes' => [
            'class' => ['button', 'delete-session-template-button', 'btn', 'btn-danger',],
          ],
        ]
        )->toRenderable();
      }

    $response['template'] = [
      '#theme' => 'session_template',
      '#template' => $session_template_data,
      '#replacements' => $replacements,
      '#actions' => $actions,
    ];

    return $response;
  }

  /**
   * Returns Session Templates management page
   *
   * @return array
   *   Content structure
   */
  public function manage() {
    $query = $this->connection->select($this->sessionTemplateManager::SESSION_TEMPLATE_TABLE, 'st', [
      'fetch' => SessionTemplate::class,
    ]);
    $query->fields('st', ['uuid', 'data', 'created', 'changed',]);
    $pager = $query->extend('Drupal\Core\Database\Query\PagerSelectExtender')->limit(25);
    $result = $pager->execute();

    $header = [
      $this->t('Title'),
      $this->t('Created'),
      $this->t('Changed'),
      $this->t('Actions'),
    ];
    $rows = [];

    foreach ($result->fetchAll() as $template) {
      $rows[] = [
        'data' => [
          'title' => Link::createFromRoute(
            $template->getTitle(),
            'session_template.preview',
            ['session_template' => $template->uuid],
            []
          ),
          'created' => $this->dateFormatter->format($template->created, 'long'),
          'changed' => $this->dateFormatter->format($template->changed, 'long'),
          'actions' => Link::createFromRoute(
            t('Delete'),
            'session_template.delete',
            ['session_template' => $template->uuid],
            [
              'attributes' => [
                'class' => ['button', 'delete-session-template-button', 'btn', 'btn-xs', 'btn-danger',],
              ],
            ]
          ),
        ],
      ];
    }

    $response['templates'] = [
      '#type' => 'table',
      '#caption' => $this->t('Session templates'),
      '#header' => $header,
      '#rows' => $rows,
      '#empty' => $this->t('No templates found'),
    ];

    $response['pager'] = [
      '#type' => 'pager',
    ];

    return $response;
  }

}
