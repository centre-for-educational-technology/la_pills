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

/**
 * Class SessionEntityController.
 */
class SessionEntityController extends ControllerBase {

  /**
   * Title.
   *
   * @return string
   *   Return Hello string.
   */
  public function dashboardTitle() {
    return 'Dashboard';
  }
  /**
   * Content.
   *
   * @return string
   *   Return Hello string.
   */
  public function dashboard(RouteMatchInterface $route_match) {
    return [
      '#theme' => 'session_entity_dashboard',
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: content'),
    ];
  }

  public function questionnaireTitle() {
    return 'Questionnaire';
  }

  public function restQuestionnaireCount(SessionEntity $session_entity) {
    if (!$session_entity->access('update')) {
      return new JsonResponse([], 403);
    }

    $connection = \Drupal::database();

    $query = $connection->select('session_questionnaire_answer', 'sqa');

    $query->condition('sqa.session_entity_uuid', $session_entity->uuid(), '=');
    $query->addField('sqa', 'questionnaire_uuid', 'uuid');
    $query->addExpression('COUNT(DISTINCT sqa.form_build_id)', 'count');
    $query->groupBy('sqa.questionnaire_uuid');

    $result = $query->execute();

    return new JsonResponse($result->fetchAll());
  }

  public function downloadAnswers(SessionEntity $session_entity, Request $request) {
    // TODO Need to make sure that downloading answers has more protection
    // Permission check is the best way forward
    if ($session_entity->uuid() !== $request->get('token')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    /*if (!$session_entity->access('update')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }*/

    $connection = \Drupal::database();

    $query = $connection->select('session_questionnaire_answer', 'sqa');

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
