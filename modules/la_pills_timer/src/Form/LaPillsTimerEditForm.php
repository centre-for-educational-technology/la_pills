<?php

namespace Drupal\la_pills_timer\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Ajax\CloseModalDialogCommand;

/**
 * Class LaPlissTimerEditForm.
 */
class LaPillsTimerEditForm extends FormBase {

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
    return 'la_pliss_timer_edit_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $timer_id = NULL) {
    $entity = [];

    $form['description'] = [
      '#type' => 'markup',
      '#markup' => '<span class="title-modal-timer">' . $this->t('Please edit Name, Color and Category and press Submit for applying changes to the current activity') . '</span>',
      '#weight' => '-10',
    ];

    if (isset($timer_id) && is_numeric($timer_id)) {
      $form['timer_id'] = array(
        '#type' => 'hidden',
        '#value' => $timer_id,
      );

      $entity = $this->entityTypeManager
        ->getStorage('la_pills_timer_entity')
        ->load($timer_id);
    }

    $form['message'] = [
      '#type' => 'markup',
      '#markup' => '<div class="status-timer-edit-form"></div>',
      '#weight' => '0',
    ];

    $form['name'] = [
      '#type' => 'textfield',
      '#required' => FALSE,
      '#title' => $this->t('Name'),
      '#weight' => '1',
      '#default_value' => ($entity->get('name')->value) ? $entity->get('name')->value : '',
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
      '#default_value' => ($entity->get('group')->value) ? $entity->get('group')->value : '',
      '#attributes' => [
        'class' => [
          'timer-group-select'
        ]
      ]
    ];

    $form['color'] = [
      '#type' => 'color',
      '#title' => $this->t('Color'),
      '#default_value' => ($entity->get('color')->value) ? $entity->get('color')->value : '',
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
        'checked' => $entity->getStatus() ? TRUE : FALSE,
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

    if (!empty($values['name']) && !empty($values['timer_id'])) {
      $result = [];
      $result['name'] = $values['name'];
      $result['timer_id'] = $values['timer_id'];

      $timer = $this->entityTypeManager
        ->getStorage('la_pills_timer_entity')
        ->load($result['timer_id']);

      if ($values['color']) {
        $result['color'] = $values['color'];
      }

      if ($values['group']) {
        $result['group'] = $values['group'];
        $old_group = $timer->get('group')->value;
      }

      if (isset($values['status'])) {
        $result['status'] = $values['status'];
      }

      foreach ($result as $key => $value) {
        if ($timer->hasField($key)) {
          $timer->set($key, $value);
        }
      }

      $timer->save();

      $data = $this->entityTypeManager
        ->getViewBuilder('la_pills_timer_entity')
        ->view($timer);

      $data = [
        '#theme' => 'la_pills_timer_elements',
        '#elements' => [$data],
      ];

      $timer_name = '.la-pills-timer-' . $result['timer_id'];

      $response->addCommand(new CloseModalDialogCommand());

      $group = $timer->get('group')->value;

      if ($group != $old_group) {
        $response->addCommand(
          new HtmlCommand(
            $timer_name,
            '' )
        );

        $response->addCommand(
          new HtmlCommand(
            '.new-timer-' . $group,
            render($data) )
        );
      } else {
        $response->addCommand(
          new HtmlCommand(
            $timer_name,
            render($data) )
        );
      }

    } else {
      $error = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this
          ->t('The name field is required.'),
        '#attributes' => ['class' => ['alert alert-danger alert-dismissible']],
      ];

      $response->addCommand(new HtmlCommand('.status-timer-edit-form', $error));
    }

    return $response;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

  }

}
