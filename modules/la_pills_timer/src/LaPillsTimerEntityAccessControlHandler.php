<?php

namespace Drupal\la_pills_timer;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the LA Pills Timer entity.
 *
 * @see \Drupal\la_pills_timer\Entity\LaPillsTimerEntity.
 */
class LaPillsTimerEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\la_pills_timer\Entity\LaPillsTimerEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished la pills timer entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published la pills timer entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit la pills timer entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete la pills timer entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add la pills timer entities');
  }

}
