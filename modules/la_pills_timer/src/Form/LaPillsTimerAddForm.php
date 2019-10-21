<?php

namespace Drupal\la_pills_timer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class LaPillsTimerAddForm.
 */
class LaPillsTimerAddForm extends FormBase {

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
    return 'la_pliss_timer_add_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<span class="title-modal-timer">' . $this->t('Please specify Name, Color and Category and press Submit for creation of new activity') . '</span>',
      '#weight' => '-10',
    ];

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="status-timer-form"></div>',
      '#weight' => '0',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Name'),
      '#weight' => '1',
    ];

    $form['group'] = [
      '#type' => 'select',
      '#title' => $this->t('Group'),
      '#options' => [
        'student' => $this->t('Student'),
        'teacher' => $this->t('Teacher'),
        'other' => $this->t('Other'),
      ],
      '#weight' => '2',
      '#default_value' => 'other',
      '#attributes' => [
        'class' => [
          'timer-group-select'
        ]
      ]
    ];

    $form['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#default_value' => '#ffffff',
      '#weight' => '3',
      '#attributes' => [
        'class' => [
          'timer-color-field'
        ]
      ]
    ];

    $form['status'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Active'),
      '#weight' => 4,
      '#attributes' => [
        'title' => $this->t('Mark as active'),
        'data-toggle' => 'tooltip',
        'checked' => TRUE,
        'class' => [
          'timer-status',
        ],
      ],
    ];

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
      '#ajax' => [
        'callback' => '::ajaxSumbitForm',
      ],
      '#weight' => '5',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  public function ajaxSumbitForm(array &$form, FormStateInterface $form_state) {
    $response = new AjaxResponse();
    $values = $form_state->getValues();

    if (!empty($values['name'])) {
      $result = [];
      $result['name'] = $values['name'];

      if ($values['color']) {
        $result['color'] = $values['color'];
      }

      if ($values['group']) {
        $result['group'] = $values['group'];
      }

      if (isset($values['status'])) {
        $result['status'] = $values['status'];
      }

      $timer = $this->entityTypeManager
        ->getStorage('la_pills_timer_entity')
        ->create($result);

      $timer->save();

      $data = $this->entityTypeManager
        ->getViewBuilder('la_pills_timer_entity')
        ->view($timer);

      $data = [
        '#theme' => 'la_pills_timer_elements',
        '#elements' => [$data],
      ];

      $group = 'new-timer-' . $result['group'];

      $response->addCommand(new CloseModalDialogCommand());
      $response->addCommand(
        new ReplaceCommand(
          '.' . $group,
          '<div class="' . $group . '"></div>' . render($data) )
      );
    } else {
      $error = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this
          ->t('The name field is required.'),
        '#attributes' => ['class' => ['alert alert-danger alert-dismissible']],
      ];

      $response->addCommand(new HtmlCommand('.status-timer-form', $error));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
