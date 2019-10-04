<?php

namespace Drupal\la_pills_timer;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of La Pills Timer Session entities.
 *
 * @ingroup la_pills_timer
 */
class LaPillsTimerSessionEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('La Pills Timer Session ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\la_pills_timer\Entity\LaPillsTimerSessionEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.la_pills_timer_session_entity.edit_form',
      ['la_pills_timer_session_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
