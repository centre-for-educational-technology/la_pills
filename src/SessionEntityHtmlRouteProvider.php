<?php

namespace Drupal\la_pills;

use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\Routing\AdminHtmlRouteProvider;
use Symfony\Component\Routing\Route;
use Drupal\la_pills\Controller\SessionEntityController;

/**
 * Provides routes for LA Pills Session entities.
 *
 * @see \Drupal\Core\Entity\Routing\AdminHtmlRouteProvider
 * @see \Drupal\Core\Entity\Routing\DefaultHtmlRouteProvider
 */
class SessionEntityHtmlRouteProvider extends AdminHtmlRouteProvider {

  /**
   * {@inheritdoc}
   */
  public function getRoutes(EntityTypeInterface $entity_type) {
    $collection = parent::getRoutes($entity_type);

    // Override for listing view, should no longer be required once the route logic is finalised
    $collection->get('entity.session_entity.collection')->setRequirement('_permission', 'view unpublished la pills session entities');

    $entity_type_id = $entity_type->id();

    if ($settings_form_route = $this->getSettingsFormRoute($entity_type)) {
      $collection->add("$entity_type_id.settings", $settings_form_route);
    }

    if ($dashboard_page_route = $this->getDashboardRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.dashboard", $dashboard_page_route);
    }

    if ($questionnaire_form_route = $this->getQuestionnaireFormRoute($entity_type)) {
      $collection->add("entity.$entity_type_id.questionnaire", $questionnaire_form_route);
    }

    return $collection;
  }

  /**
   * Gets the settings form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getSettingsFormRoute(EntityTypeInterface $entity_type) {
    if (!$entity_type->getBundleEntityType()) {
      $route = new Route("/admin/structure/{$entity_type->id()}/settings");
      $route
        ->setDefaults([
          '_form' => 'Drupal\la_pills\Form\SessionEntitySettingsForm',
          '_title' => "{$entity_type->getLabel()} settings",
        ])
        ->setRequirement('_permission', $entity_type->getAdminPermission())
        ->setOption('_admin_route', TRUE);

      return $route;
    }
  }

  /**
   * Gets the dashboard page route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getDashboardRoute(EntityTypeInterface $entity_type) {
    if ($entity_type
      ->hasLinkTemplate('dashboard')) {
      $entity_type_id = $entity_type
        ->id();
      $route = new Route($entity_type
        ->getLinkTemplate('dashboard'));
      $route
        ->setDefault('_controller', SessionEntityController::class . '::dashboard');
      $route
        ->setDefault('_title_callback', SessionEntityController::class . '::dashboardTitle');
      $route
        ->setDefault('entity_type_id', $entity_type
        ->id());
      $route
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
        $entity_type_id => [
          'type' => 'entity:' . $entity_type_id,
        ],
      ]);
      return $route;
    }
  }

  /**
   * Gets the questionnaire form route.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type.
   *
   * @return \Symfony\Component\Routing\Route|null
   *   The generated route, if available.
   */
  protected function getQuestionnaireFormRoute(EntityTypeInterface $entity_type) {
    if ($entity_type
      ->hasLinkTemplate('questionnaire')) {
      $entity_type_id = $entity_type
        ->id();
      $route = new Route($entity_type
        ->getLinkTemplate('questionnaire'));

      // Use the edit form handler, if available, otherwise default.
      $operation = 'default';
      if ($entity_type
        ->getFormClass('questionnaire')) {
        $operation = 'questionnaire';
      }
      $route
        ->setDefaults([
        '_entity_form' => "{$entity_type_id}.{$operation}",
        '_title_callback' => 'Drupal\la_pills\Controller\SessionEntityController::questionnaireTitle',
      ])
        ->setRequirement('_entity_access', "{$entity_type_id}.view")
        ->setOption('parameters', [
        $entity_type_id => [
          'type' => 'entity:' . $entity_type_id,
        ],
      ]);

      // Entity types with serial IDs can specify this in their route
      // requirements, improving the matching process.
      if ($this
        ->getEntityTypeIdKeyType($entity_type) === 'integer') {
        $route
          ->setRequirement($entity_type_id, '\\d+');
      }
      return $route;
    }
  }

}
