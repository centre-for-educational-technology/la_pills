<?php

namespace Drupal\la_pills_rest\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Drupal\la_pills\Entity\SessionEntity;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "la_pills_sessions_answers_resource",
 *   label = @Translation("LAPills Session Answers resource"),
 *   uri_paths = {
 *     "canonical" = "api/la_pills/session/{id}/answers"
 *   }
 * )
 */
class LaPillsSessionAnswersResource extends ResourceBase {

  use FromAndUntilRestResourceTrait;

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

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
  protected $currentRequest;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('la_pills_rest');
    $instance->currentUser = $container->get('current_user');
    $instance->connection = $container->get('database');
    $instance->currentRequest = $container->get('request_stack')->getCurrentRequest();
    return $instance;
  }

    /**
     * Responds to GET requests.
     *
     * @param int $id
     *   Session id.
     * @param Request $request
     *   Request object.
     *
     * @return \Drupal\rest\ResourceResponse
     *   The HTTP response object.
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     *   Throws exception expected.
     */
    public function get(int $id, Request $request) {
      // You must to implement the logic of your REST Resource here.
      // Use current user after pass authentication to validate access.
      if (!$this->currentUser->hasPermission('access content')) {
        throw new AccessDeniedHttpException();
      }

      $this->validateFromAndUntil($request);

      $session = SessionEntity::load($id);

      // TODO See if NotFound is a better exception choice
      if (!$session) {
        throw new BadRequestHttpException();
      }

      $data = [
        'answers' => [],
      ];

      $query = $this->connection->select('session_questionnaire_answer', 'sqa');
      $query->condition('sqa.session_entity_uuid', $session->uuid(), '=');

      if ($this->hasFromParam($request) && $from = $this->fromTimestampt($request)) {
        $query->condition('created', $from, '>=');
      }
      if ($this->hasUntilParam($request) && $until = $this->untilTimestamp($request)) {
        $query->condition('created', $until, '<=');
      }

      $query->fields('sqa', ['questionnaire_uuid', 'question_uuid', 'session_id', 'form_build_id', 'user_id', 'name', 'answer', 'created',]);
      $query->addExpression('FROM_UNIXTIME(created)', 'created');

      $result = $query->execute();

      while ($row = $result->fetchObject()) {
        $data['answers'][] = [
          'session_id' => $session->id(),
          'questionnaire_uuid' => $row->questionnaire_uuid,
          'question_uuid' => $row->question_uuid,
          'user_session_id' => $row->session_id,
          'form_build_id' => $row->form_build_id,
          'user_id' => $row->user_id,
          'name' => $row->name,
          'answer' => $row->answer,
          'created' => $row->created,
        ];
      }

      $response = new ResourceResponse($data, 200);

      // TODO See if setting it non-cacheable is a better approach, see URL for details
      // https://drupal.stackexchange.com/a/224508/92770
      $response->addCacheableDependency($data);

      return $response;
     }

}
