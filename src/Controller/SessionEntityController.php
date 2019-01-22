<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\la_pills\Entity\SessionEntity;
use \Drupal\Core\Routing\RouteMatchInterface;
use \Drupal\Core\Entity\EntityInterface;

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
  public function dashboard(RouteMatchInterface $route_match, EntityInterface $_entity = NULL) {
    return [
      '#theme' => 'session_entity_dashboard',
      '#type' => 'markup',
      '#markup' => $this->t('Implement method: content'),
    ];
  }

  public function questionnaireTitle() {
    return 'Questionnaire';
  }

}
