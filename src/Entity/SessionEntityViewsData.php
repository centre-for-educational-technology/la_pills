<?php

namespace Drupal\la_pills\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for LA Pills Session entities.
 */
class SessionEntityViewsData extends EntityViewsData {

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
