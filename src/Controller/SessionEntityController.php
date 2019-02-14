<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\la_pills\Entity\SessionEntity;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Random;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;

/**
 * Class SessionEntityController.
 */
class SessionEntityController extends ControllerBase {

  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Controller constructor
   *
   * @param Drupal\Core\Database\Connection $connection
   *   Database connection
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $connection = $container->get('database');

    return new static($connection);
  }

  /**
   * Returns title for dashboard page
   *
   * @return string
   *   Title text
   */
  public function dashboardTitle() {
    return 'Dashboard';
  }

  /**
   * Returns dashboard page structure
   *
   * @return array
   *   Content structure
   */
  public function dashboard(SessionEntity $session_entity) {
    $structure = $session_entity->getSessionTemplateData();
    $response['dashboard'] = [
      '#attached' => [
        'library' => [
          'la_pills/session_entity_dashboard'
        ],
      ],
    ];

    foreach ($structure['questionnaires'] as $questionnaire) {
      $response_count = 90; // XXX This should not be hard-coded
      $response[$questionnaire['uuid']] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['questionnaire'],
        ],
      ];
      $response[$questionnaire['uuid']]['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $questionnaire['title'] . (($response_count > 0) ? ' (' . $response_count . ')' : ''),
      ];

      foreach ($questionnaire['questions'] as $question) {
        $response[$questionnaire['uuid']][$question['uuid']] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['question'],
            'data-question-type' => str_replace(' ', '-', strtolower($question['type'])),
            'data-uuid' => 'question-' . $questionnaire['uuid'] . '-' . $question['uuid'],
          ],
        ];
        $response[$questionnaire['uuid']][$question['uuid']]['heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $question['title'],
        ];
      }
    }

    return $response;
    return [
      '#theme' => 'session_entity_dashboard',
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: content'),
    ];
  }

  /**
   * Returns title for questionnaire page
   *
   * @return string
   *   Title text
   */
  public function questionnaireTitle() {
    return 'Questionnaire';
  }

  /**
   * Responds with JOSN data for questionnaire answer count
   *
   * @param Drupal\la_pills\Entity\SessionEntity $session_entity
   *   Session Entity object
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with data
   */
  public function restQuestionnaireCount(SessionEntity $session_entity) {
    if (!$session_entity->access('update')) {
      return new JsonResponse([], 403);
    }

    $query = $this->connection->select('session_questionnaire_answer', 'sqa');

    $query->condition('sqa.session_entity_uuid', $session_entity->uuid(), '=');
    $query->addField('sqa', 'questionnaire_uuid', 'uuid');
    $query->addExpression('COUNT(DISTINCT sqa.form_build_id)', 'count');
    $query->groupBy('sqa.questionnaire_uuid');

    $result = $query->execute();

    return new JsonResponse($result->fetchAll());
  }

  /**
   * Triggers file download with all the answers for a Session Entity
   *
   * @param Drupal\la_pills\Entity\SessionEntity $session_entity
   *   Session Entity object
   * @param Symfony\Component\HttpFoundation\Request       $request
   *   Request object
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Response object
   */
  public function downloadAnswers(SessionEntity $session_entity, Request $request) {
    // TODO Need to make sure that downloading answers has more protection
    // Permission check is the best way forward
    if ($session_entity->uuid() !== $request->get('token')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    /*if (!$session_entity->access('update')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }*/

    $query = $this->connection->select('session_questionnaire_answer', 'sqa');

    $query->condition('sqa.session_entity_uuid', $session_entity->uuid(), '=');
    $query->fields('sqa', ['questionnaire_uuid', 'question_uuid', 'session_id', 'form_build_id', 'answer', 'created',]);
    $query->addExpression('FROM_UNIXTIME(created)', 'created');

    $result = $query->execute();

    $handle = fopen('php://temp', 'wb');

    fputcsv($handle, ['Session title', 'Questionnaire title', 'Question title', 'Question type', 'Session identifier', 'Form submission identifier', 'Answer', 'Created',]);

    $template = $session_entity->getSessionTemplateData();
    $salt = Random::string();

    while ($row = $result->fetchObject()) {
      fputcsv($handle, [$session_entity->getName(), $template['questionnaires'][$row->questionnaire_uuid]['title'], $template['questions'][$row->question_uuid]['title'], $template['questions'][$row->question_uuid]['type'], hash('sha256', $row->session_id . $salt), hash('sha256', $row->form_build_id . $salt), $row->answer, $row->created,]);
    }
    rewind($handle);

    $response = new Response(stream_get_contents($handle));
    fclose($handle);

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition','attachment; filename="answers.csv"');

    return $response;
  }

}
