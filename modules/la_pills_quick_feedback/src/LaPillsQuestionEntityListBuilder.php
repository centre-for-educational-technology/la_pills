<?php

namespace Drupal\la_pills_quick_feedback;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of LaPills Question Entity entities.
 *
 * @ingroup la_pills_quick_feedback
 */
class LaPillsQuestionEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('LaPills Question Entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.la_pills_question_entity.edit_form',
      ['la_pills_question_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
