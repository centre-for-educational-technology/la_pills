<?php

namespace Drupal\la_pills_quick_feedback\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for LaPills Question Entity entities.
 */
class LaPillsQuestionEntityViewsData extends EntityViewsData {

  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // Additional information for Views integration, such as table joins, can be
    // put here.
    return $data;
  }

}
