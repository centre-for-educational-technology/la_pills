<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Entity\ContentEntityDeleteForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RemoveCommand;

/**
 * Allows to use AJAX with ContentEntityDeleteForm.
 */
abstract class ContentEntityAjaxDeleteForm extends ContentEntityDeleteForm {

  /**
   * Retuns a selector of the element to be used for RemoveCommand.
   *
   * @return string
   *   Selector for element removal.
   */
  abstract function rowRemoveCommandSelector() : string;

  /**
   * A title override for the form if AJAX is used.
   *
   * @return string
   *   Translated title.
   */
  abstract function formTitleOverride() : string;

  /**
   * AJAX removal callback.
   *
   * @param  array              $form
   *   An array with renderable form structure
   * @param  Drupal\Core\Form\FormStateInterface $form_state
   *   FormState ofject
   * @return Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse object with commands
   */
  public function ajaxRemove(array $form, FormStateInterface $form_state) {
    $entity = $this->getEntity();
    $entity->delete();

    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());
    $response->addCommand(new RemoveCommand($this->rowRemoveCommandSelector()));

    return $response;
  }

  /**
   * AJAX calcel action callback.
   *
   * @param  array              $form
   *   An array with renderable form structure
   * @param  Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   AjaxResponse object with dialog close command.
   */
  public function ajaxCancel(array $form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);

    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxRemove',
      ];
      $form['actions']['cancel'] = [
        '#type' => 'button',
        '#value' => $this->t('Cancel'),
        '#ajax' => [
          'callback' => '::ajaxCancel',
        ],
        '#weight' => 10,
      ];
      $form['#title'] = $this->formTitleOverride();
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if ($this->getRequest()->isXmlHttpRequest()) {
      return;
    }

    parent::submitForm($form, $form_state);
  }

}
