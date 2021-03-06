<?php

namespace Drupal\la_pills_quick_feedback;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the LaPills Question Entity entity.
 *
 * @see \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntity.
 */
class LaPillsQuestionEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface $entity */

    switch ($operation) {

      case 'view':
        if ($entity->isOwner($account)) {
          return AccessResult::allowed();
        }

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished lapills question entity entities');
        }

        return AccessResult::allowedIfHasPermission($account, 'view published lapills question entity entities');

      case 'update':

        if ($entity->isOwner($account)) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'edit lapills question entity entities');

      case 'delete':

        if ($entity->isOwner($account)) {
          return AccessResult::allowed();
        }

        return AccessResult::allowedIfHasPermission($account, 'delete lapills question entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add lapills question entity entities');
  }


}
