<?php

namespace Drupal\la_pills;

use Drupal\la_pills\Entity\SessionEntity;
use Drupal\Core\Link;

/**
 * Used as a helper class with static methods to have reusable code for generating renderable elements
 */
class RenderableHelper {

  /**
   * Returns renderable link for downloading answers
   *
   * @param  Drupal\la_pills\Entity\SessionEntity $entity
   *   Session Entity object
   *
   * @return Drupal\Core\Link
   *   Renderable button-like link for downloading Session Entity answers
   */
  public static function downloadAnswersLink(SessionEntity $entity) {
    return Link::createFromRoute(
      t('Download answers'),
      'session_entity.download_answers',
      ['session_entity' => $entity->id()],
      ['query' => ['token' => $entity->uuid()], 'attributes' => ['class' => ['button', 'download-answers-button']]]
    );
  }
}
