<?php

namespace Drupal\la_pills_onboarding\Form;

use Drupal\la_pills\Form\ContentEntityAjaxDeleteForm;

/**
 * Provides a form for deleting User package entities.
 *
 * @ingroup la_pills_onboarding
 */
class LaPillsUserPackageEntityDeleteForm extends ContentEntityAjaxDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function rowRemoveCommandSelector() : string {
    return '#user-package-' . $this->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function formTitleOverride() : string {
    return $this->t('Remove User Package');
  }

}
