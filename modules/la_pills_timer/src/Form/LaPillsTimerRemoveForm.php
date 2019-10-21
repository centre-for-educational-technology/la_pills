<?php

namespace Drupal\la_pills_timer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class LaPillsTimerRemoveForm.
 */
class LaPillsTimerRemoveForm extends FormBase {

  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }
  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'la_pliss_timer_remove_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $timer_id = NULL) {
    if (isset($timer_id) && is_numeric($timer_id)) {
      $form['timer_id'] = array(
        '#type' => 'hidden',
        '#value' => $timer_id,
      );
    }

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<span class="title-modal-timer">' . $this->t('Make sure that you really need to delete this activity. This operation cannot be reverted.') . '</span>',
      '#weight' => '-10',
    ];

    $form['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['timer-delete-actions']
      ]
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#ajax' => [
        'callback' => '::ajaxDeleteForm',
      ]
    ];

    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#ajax' => [
        'callback' => '::ajaxCancelForm',
      ]
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function ajaxCancelForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();

    return $response->addCommand(new CloseModalDialogCommand());
  }

  public function ajaxDeleteForm(array &$form, FormStateInterface $form_state, &$timer_id = NULL) {
    $response = new AjaxResponse();
    $timer_id = $form_state->getValue('timer_id');

    if (!empty($timer_id)) {
      $timer = $this->entityTypeManager->getStorage('la_pills_timer_entity')->load($timer_id);
      $timer->delete();

      $timer_name = 'la-pills-timer-' . $timer_id;

      $message = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this
          ->t('Removed.'),
        '#attributes' => [
            'class' => ['la-pills-timer', $timer_name, 'alert', 'alert-success'],
            'role' => 'alert',
        ],
      ];

      $response->addCommand(
        new ReplaceCommand(
          '.' . $timer_name,
          $message)
      );
      $response->addCommand(new InvokeCommand('.' . $timer_name, 'fadeOut', [3000]));
    }

    $response->addCommand(new CloseModalDialogCommand());

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
