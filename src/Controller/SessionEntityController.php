<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\la_pills\Entity\SessionEntity;
use \Drupal\Core\Routing\RouteMatchInterface;
use \Drupal\Core\Entity\EntityInterface;
use \Symfony\Component\HttpFoundation\JsonResponse;

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

}
