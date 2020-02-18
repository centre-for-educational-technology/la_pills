<?php

namespace Drupal\la_pills_onboarding;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of User package entities.
 *
 * @ingroup la_pills_onboarding
 */
class LaPillsUserPackageEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('User package ID');
    $header['name'] = $this->t('Name');
    $header['owner'] = $this->t('Owner');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.la_pills_user_package.canonical',
      ['la_pills_user_package' => $entity->id()]
    );
    $row['owner'] = $entity->getOwner()->toLink();
    return $row + parent::buildRow($entity);
  }

}
