<?php

namespace Drupal\la_pills;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of LA Pills Session entities.
 *
 * @ingroup la_pills
 */
class SessionEntityListBuilder extends EntityListBuilder {


  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var $entity \Drupal\la_pills\Entity\SessionEntity */
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.session_entity.canonical',
      ['session_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
