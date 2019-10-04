<?php

namespace Drupal\la_pills_timer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of LA Pills Timer entities.
 *
 * @ingroup la_pills_timer
 */
class LaPillsTimerEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('LA Pills Timer ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\la_pills_timer\Entity\LaPillsTimerEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.la_pills_timer_entity.edit_form',
      ['la_pills_timer_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
