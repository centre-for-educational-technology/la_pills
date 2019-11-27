<?php

namespace Drupal\la_pills_quick_feedback;

use Drupal\Core\Entity\EntityAccessControlHandler;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Access\AccessResult;

/**
 * Access controller for the LaPills Questionnaire Entity entity.
 *
 * @see \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntity.
 */
class LaPillsQuestionnaireEntityAccessControlHandler extends EntityAccessControlHandler {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    /** @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntityInterface $entity */

    switch ($operation) {

      case 'view':

        if (!$entity->isPublished()) {
          return AccessResult::allowedIfHasPermission($account, 'view unpublished lapills questionnaire entity entities');
        }


        return AccessResult::allowedIfHasPermission($account, 'view published lapills questionnaire entity entities');

      case 'update':

        return AccessResult::allowedIfHasPermission($account, 'edit lapills questionnaire entity entities');

      case 'delete':

        return AccessResult::allowedIfHasPermission($account, 'delete lapills questionnaire entity entities');
    }

    // Unknown operation, no opinion.
    return AccessResult::neutral();
  }

  /**
   * {@inheritdoc}
   */
  protected function checkCreateAccess(AccountInterface $account, array $context, $entity_bundle = NULL) {
    return AccessResult::allowedIfHasPermission($account, 'add lapills questionnaire entity entities');
  }


}
