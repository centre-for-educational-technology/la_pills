<?php

namespace Drupal\la_pills\Form;

/**
 * Provides a form for deleting LA Pills Session entities.
 *
 * @ingroup la_pills
 */
class SessionEntityDeleteForm extends ContentEntityAjaxDeleteForm {

  /**
   * {@inheritdoc}
   */
  public function getCancelUrl() {
    return $this->getEntity()->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  public function getRedirectUrl() {
    $entity = $this->getEntity();

    if ($entity->isOwner(\Drupal::currentUser())) {
      return $entity->toUrl('mine');
    }

    return $entity->toUrl('collection');
  }

  /**
   * {@inheritdoc}
   */
  function rowRemoveCommandSelector() : string {
    return '#session-entity-' . $this->getEntity()->id();
  }

  /**
   * {@inheritdoc}
   */
  function formTitleOverride() : string {
    return $this->t('Remove data gathering session');
  }

}
