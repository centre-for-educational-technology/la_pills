<?php

namespace Drupal\la_pills;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Link;
use Drupal\la_pills\RenderableHelper;
use Drupal\Core\Render\Markup;

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
    $header['template'] = $this->t('Session template');
    $header['code'] = $this->t('Code');
    $header['answers'] = $this->t('Answers');
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
    $row['session_template'] = $entity->getSessionTemplateData()['context']['title'];
    $row['code'] = '';
    $row['answers'] = '';
    if ($entity->access('update')) {
      $row['answers'] = RenderableHelper::downloadAnswersLink($entity, ['btn-xs']);
      $row['code'] = Markup::create('<strong>' . $entity->getCode() . '</strong>');
    }
    return $row + parent::buildRow($entity);
  }

}
