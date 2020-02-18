<?php

namespace Drupal\la_pills_quick_feedback\Form;

use Drupal\la_pills\Form\ContentEntityAjaxDeleteForm;

/**
 * Provides a form for deleting LaPills Question Entity entities.
 *
 * @ingroup la_pills_quick_feedback
 */
class LaPillsQuestionEntityDeleteForm extends ContentEntityAjaxDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function rowRemoveCommandSelector() : string {
    return '#quick-feedback-item-' . $this->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  public function formTitleOverride() : string {
    return $this->t('Remove Quick Feedback item');
  }

}
