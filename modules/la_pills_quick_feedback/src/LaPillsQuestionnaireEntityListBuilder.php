<?php

namespace Drupal\la_pills_quick_feedback;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;

/**
 * Defines a class to build a listing of LaPills Questionnaire Entity entities.
 *
 * @ingroup la_pills_quick_feedback
 */
class LaPillsQuestionnaireEntityListBuilder extends EntityListBuilder {

  /**
   * {@inheritdoc}
   */
  public function buildHeader() {
    $header['id'] = $this->t('LaPills Questionnaire Entity ID');
    $header['name'] = $this->t('Name');
    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity) {
    /* @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntity $entity */
    $row['id'] = $entity->id();
    $row['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.la_pills_questionnaire_entity.edit_form',
      ['la_pills_questionnaire_entity' => $entity->id()]
    );
    return $row + parent::buildRow($entity);
  }

}
