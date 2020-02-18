<?php

namespace Drupal\la_pills_onboarding;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the User package entity.
 *
 * @see \Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntity.
 */
class LaPillsUserPackageEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished user package entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published user package entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit user package entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete user package entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add user package entities');
  }


}
