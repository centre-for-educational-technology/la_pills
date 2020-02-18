<?php

namespace Drupal\la_pills_onboarding\Entity;

use Drupal\views\EntityViewsData;

/**
 * Provides Views data for User package entities.
 */
class LaPillsUserPackageEntityViewsData extends EntityViewsData {

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
