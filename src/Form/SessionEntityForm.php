<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form controller for LA Pills Session edit forms.
 *
 * @ingroup la_pills
 */
class SessionEntityForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $session_entity = null) {
    /* @var $entity \Drupal\la_pills\Entity\SessionEntity */
    $form = parent::buildForm($form, $form_state);

    if ($form_state->getBuildInfo()['form_id'] === 'session_entity_edit_form') {
      $form['template']['widget']['#disabled'] = TRUE;
    }

    $entity = $this->entity;

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        \Drupal::messenger()->addMessage($this->t('Created the %label LA Pills Session.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label LA Pills Session.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.session_entity.canonical', ['session_entity' => $entity->id()]);
  }

}
