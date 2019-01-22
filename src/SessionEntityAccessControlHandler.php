<?php

namespace Drupal\la_pills;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the LA Pills Session entity.
 *
 * @see \Drupal\la_pills\Entity\SessionEntity.
 */
class SessionEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\la_pills\Entity\SessionEntityInterface $entity */
    switch ($operation) {
      case 'view':
        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished la pills session entities');
        }
        return AccessResult::allowedIfHasPermission($account, 'view published la pills session entities');

      case 'update':
        return AccessResult::allowedIfHasPermission($account, 'edit la pills session entities');

      case 'delete':
        return AccessResult::allowedIfHasPermission($account, 'delete la pills session entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add la pills session entities');
  }

}
