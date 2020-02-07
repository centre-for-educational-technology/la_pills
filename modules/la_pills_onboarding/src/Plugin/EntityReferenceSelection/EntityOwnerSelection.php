<?php

namespace Drupal\la_pills_onboarding\Plugin\EntityReferenceSelection;
use Drupal\Core\Entity\Plugin\EntityReferenceSelection\DefaultSelection;

/**
 * Defines an alternative to the default Entity Reference Selection plugin.
 * Uses entity owner as a condition to the list of available entities.
 *
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionPluginManager
 * @see \Drupal\Core\Entity\Annotation\EntityReferenceSelection
 * @see \Drupal\Core\Entity\EntityReferenceSelection\SelectionInterface
 * @see \Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver
 * @see plugin_api
 *
 * @EntityReferenceSelection(
 *   id = "entity_owner",
 *   label = @Translation("Entity Owner"),
 *   group = "entity_owner",
 *   weight = 0,
 *   deriver = "Drupal\Core\Entity\Plugin\Derivative\DefaultSelectionDeriver"
 * )
 */
class EntityOwnerSelection extends DefaultSelection {

  /**
   * {@inheritdoc}
   */
  public function entityQueryAlter(\Drupal\Core\Database\Query\SelectInterface $query) {
    $configuration = $this
      ->getConfiguration();
    $target_type = $configuration['target_type'];
    $entity_type = $this->entityTypeManager
      ->getDefinition($target_type);
    $entity = $configuration['entity'];

    // TODO Consider only applying the condition if "uid" key is present
    // XXX This alteration needs 'base_table.' to be added from some reason
    // XXX This seems to be failing when using autocomplete, probably due to
    // entity not having user identifier set there and fallback to current user
    // could do the trick.
    $query->condition('base_table.' . $entity_type->getKey('uid'), $entity->getOwnerId());
  }

}
