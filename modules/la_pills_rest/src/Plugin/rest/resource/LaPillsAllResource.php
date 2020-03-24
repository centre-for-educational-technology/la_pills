<?php

namespace Drupal\la_pills_rest\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "la_pills_all_resource",
 *   label = @Translation("LAPills all data resource"),
 *   uri_paths = {
 *     "canonical" = "api/la_pills/all"
 *   }
 * )
 */
class LaPillsAllResource extends ResourceBase {

  use FromAndUntilRestResourceTrait;

  /**
   * Default results limit
   * @var integer
   */
  const LIMIT_DEFAULT = 50;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Entity type manager instance.
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Database connection istance.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('la_pills_rest');
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->connection = $container->get('database');

    return $instance;
  }

  /**
   * Returns current page number with fallback to zero.
   *
   * @throws BadRequestHttpException
   *   If value is less than zero
   *
   * @return int
   *   Current page number
   */
  private function getPage() : int {
    $page = (int)$this->request->query->get('page');

    if ($page < 0) {
      throw new BadRequestHttpException('Malformed page parameter! Page value can not be less than zero.');
    }

    return $page;
  }

  /**
   * Returns limit value, defaulting to constant value.
   *
   * @throws BadRequestHttpException
   *   If limit is not berween 1 and 500
   *
   * @return int [description]
   */
  private function getLimit() : int {
    if (!$this->request->query->has('limit')) {
      return self::LIMIT_DEFAULT;
    }

    $limit = (int)$this->request->query->get('limit');

    if (!($limit >= 1 && $limit <= 500)) {
      throw new BadRequestHttpException('Malformed limit parameter! Allowed limit values are from 1 to 500.');
    }

    return $limit;
  }

  /**
   * Returns RFC3339 representation of a timestamp.
   *
   * @param  int    $timestamp
   *   Timestamp value
   *
   * @return string
   *   Formatted timestamp
   */
  private function formatTimestamp(int $timestamp) : string {
    return DrupalDateTime::createFromTimestamp($timestamp)
      ->format(\DateTime::RFC3339);
  }

  /**
   * Responds to GET requests.
   *
   * @param Request $request
   *   Request object.
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(Request $request) {
    $this->request = $request;

    $this->validateFromAndUntil($request);

    $range_start = 0;
    $range_length = $this->getLimit();

    if ($this->hasQueryParam($request, 'page')) {
      $range_start = $this->getPage() * $range_length;
    }

    $data = [
      'meta' => [
        'totalPages' => 0,
      ],
      'sessions' => [],
      'answers' => [],
    ];

    $session_query = \Drupal::entityQuery('session_entity');

    if ($this->hasQueryParam($request, 'user_id')) {
      $session_query->condition('user_id', $request->query->get('user_id'), '=');
    }
    if ($this->hasFromParam($request) && $from = $this->fromTimestamp($request)) {
      $session_query->condition('created', $from, '>=');
    }
    if ($this->hasUntilParam($request) && $until = $this->untilTimestamp($request)) {
      $session_query->condition('created', $until, '<=');
    }

    $count_session_query = clone $session_query;
    $sessions_count = $count_session_query->count()->execute();

    $total_pages = ceil($sessions_count / $range_length);

    $session_query->range($range_start, $range_length);
    $session_query->sort('created', 'ASC');

    $ids = $session_query->execute();

    $sessions = $this->entityTypeManager
      ->getStorage('session_entity')
      ->loadMultiple($ids);

    if ($sessions) {
      foreach ($sessions as $session) {
        $template = $session->getSessionTemplate();

        // Allow template data to be changed or extended
        $tdata = $template->getData();
        \Drupal::moduleHandler()->alter('la_pills_session_template_data', $tdata, $session);

        if (isset($tdata['context']['date']) && $tdata['context']['date']) {
          $tdata['context']['date'] = $this->formatTimestamp($tdata['context']['date']);
        }

        $data['sessions'][] = [
          'id' => (int)$session->id(),
          'name' => $session->getName(),
          'created' => $this->formatTimestamp($session->getCreatedTime()),
          'owner' => (int)$session->getOwnerId(),
          'published' => $session->isPublished(),
          'active' => $session->isActive(),
          'allow_anonymous_responses' => $session->getAllowAnonymousResponses(),
          'template' => $tdata,
        ];
      }
    }

    $answer_query = $this->connection->select('session_questionnaire_answer', 'sqa');
    $answer_query->innerJoin('session_entity', 'se', 'se.uuid = sqa.session_entity_uuid');

    if ($this->hasQueryParam($request, 'user_id')) {
      $answer_query->condition('se.user_id', $request->query->get('user_id'), '=');
    }
    if ($this->hasFromParam($request) && $from = $this->fromTimestamp($request)) {
      $answer_query->condition('sqa.created', $from, '>=');
    }
    if ($this->hasUntilParam($request) && $until = $this->untilTimestamp($request)) {
      $answer_query->condition('sqa.created', $until, '<=');
    }

    $answer_query->fields('sqa', ['questionnaire_uuid', 'question_uuid', 'session_id', 'form_build_id', 'user_id', 'name', 'answer', 'created']);
    $answer_query->addField('se', 'id', 'session_entity_id');

    $count_answer_query = clone $answer_query;
    $answers_count = $count_answer_query->countQuery()->execute()->fetchField();

    if (ceil($answers_count / $range_length) > $total_pages) {
      $total_pages = ceil($answers_count / $range_length);
    }

    $answer_query->range($range_start, $range_length);
    $answer_query->orderBy('created', 'ASC');

    $result = $answer_query->execute();

    while ($row = $result->fetchObject()) {
      $data['answers'][] = [
        'session_id' => (int)$row->session_entity_id,
        'questionnaire_uuid' => $row->questionnaire_uuid,
        'question_uuid' => $row->question_uuid,
        'user_session_id' => $row->session_id,
        'form_build_id' => $row->form_build_id,
        'user_id' => !is_null($row->user_id) ? (int)$row->user_id : $row->user_id,
        'name' => $row->name,
        'answer' => $row->answer,
        'created' => $this->formatTimestamp($row->created),
      ];
    }

    $data['meta']['totalPages'] = $total_pages;

    $data['links']['self'] = Url::fromUri($request->getUri(), [
      'absolute' => TRUE,
    ])->toString();
    if ($this->getPage() > 0) {
      $data['links']['prev'] = Url::fromUri($request->getUri(), [
        'absolute' => TRUE,
        'query' => [
          'page' => $this->getPage() - 1,
        ],
      ])->toString();
    }
    if ($total_pages > 1 && $this->getPage() < ($total_pages - 1)) {
      $data['links']['next'] = Url::fromUri($request->getUri(), [
        'absolute' => TRUE,
        'query' => [
          'page' => $this->getPage() + 1,
        ],
      ])->toString();
    }

    $response = new ResourceResponse($data, 200);

    // TODO See if setting it non-cacheable is a better approach, see URL for details
    // https://drupal.stackexchange.com/a/224508/92770
    $response->addCacheableDependency($data);

    return $response;
  }

}
