<?php

namespace Drupal\la_pills_quick_feedback\Form;

use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Entity\EntityRepositoryInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Ajax\BeforeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Form controller for LaPills Question Entity edit forms.
 *
 * @ingroup la_pills_quick_feedback
 */
class LaPillsQuestionEntityForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * Constructs a new LaPillsQuestionEntityForm.
   *
   * @param \Drupal\Core\Entity\EntityRepositoryInterface $entity_repository
   *   The entity repository service.
   * @param \Drupal\Core\Entity\EntityTypeBundleInfoInterface $entity_type_bundle_info
   *   The entity type bundle service.
   * @param \Drupal\Component\Datetime\TimeInterface $time
   *   The time service.
   * @param \Drupal\Core\Session\AccountProxyInterface $account
   *   The current user account.
   */
  public function __construct(EntityRepositoryInterface $entity_repository, EntityTypeBundleInfoInterface $entity_type_bundle_info = NULL, TimeInterface $time = NULL, AccountProxyInterface $account) {
    parent::__construct($entity_repository, $entity_type_bundle_info, $time);

    $this->account = $account;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    return new static(
      $container->get('entity.repository'),
      $container->get('entity_type.bundle.info'),
      $container->get('datetime.time'),
      $container->get('current_user')
    );
  }

  /**
   * Returns entity options
   *
   * @return array
   *   Entity options or an empty array
   */
  private function getEntityOptions() {
    $data = $this->entity->get('data')->getValue();

    return $data[0]['options'] ?? [];
  }

  /**
   * Returns entity options count. Defaults to four if options are empty
   * @return int
   *   Options counr or four if empty
   */
  private function getEntityOptionsCount() {
    $data = $this->entity->get('data')->getValue();

    if (isset($data[0]['options']) && count($data[0]['options']) > 0) {
      return count($data[0]['options']);
    }

    return 4;
  }

  /**
   * Returns range min value
   *
   * @return mixed
   *   Min value or empty string
   */
  private function getEntityRangeMin() {
    $data = $this->entity->get('data')->getValue();

    return $data[0]['range']['min'] ?? '';
  }

  /**
   * Returns range max value
   *
   * @return mixed
   *   Max value or empty string
   */
  private function getEntityRangeMax() {
    $data = $this->entity->get('data')->getValue();

    return $data[0]['range']['max'] ?? '';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntity $entity */
    $form = parent::buildForm($form, $form_state);

    $form['status']['#access'] = FALSE;
    $form['user_id']['#access'] = FALSE;

    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['ajax_messages'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'result-message',
        ],
        '#weight' => -50,
      ];

      // Set ajaxSave action and disable any submit callbacks
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxSave',
      ];
      $form['actions']['submit']['#submit'] = [];

      if (isset($form['actions']['delete'])) {
        $form['actions']['delete']['#access'] = FALSE;
      }
    }

    if (!$this->entity->isNew()) {
      $form['type']['widget']['#disabled'] = TRUE;
    }

    $form['range'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Range'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#states' => [
        'visible' => [
          ':input[name="type"]' => ['value' => 'scale'],
        ],
      ],
      '#weight' => $form['type']['#weight'],
    ];
    $form['range']['range_min'] = [
      '#type' => 'number',
      '#title' => $this->t('Min'),
      '#attributes' => [
        'id' => 'range-min',
      ],
      '#states' => [
        'enabled' => [
          ':input[name="type"]' => ['value' => 'scale'],
        ],
      ],
      '#default_value' => $this->getEntityRangeMin(),
    ];
    $form['range']['range_max'] = [
      '#type' => 'number',
      '#title' => $this->t('Max'),
      '#attributes' => [
        'id' => 'range-max',
      ],
      '#states' => [
        'enabled' => [
          ':input[name="type"]' => ['value' => 'scale'],
        ],
      ],
      '#default_value' => $this->getEntityRangeMax(),
    ];

    $form['options'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'options-container',
      ],
      '#states' => [
        'visible' => [
          ':input[name="type"]' => [
            ['value' => 'multi-choice'],
            ['value' => 'checkboxes'],
          ],
        ],
      ],
      '#weight' => $form['type']['#weight'],
    ];
    $form['options']['options'] = [
      '#tree' => TRUE,
      '#type' => 'fieldset',
      '#title' => $this->t('Options'),
      '#collapsible' => FALSE,
      '#collapsed' => FALSE,
      '#attributes' => [
        'id' => 'options-wrapper',
      ],
    ];
    $form['options']['add_new_option'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add new option'),
      '#submit' => array('::addNewOption'),
      '#attributes' => [
        'class' => ['btn', 'btn-sm',],
      ],
      '#ajax' => [
        'callback' => '::ajaxAddNewOption',
        'wrapper' => 'options-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    $num_options = $form_state->get('num_options');

    if (empty($num_options)) {
      $num_options = $this->getEntityOptionsCount();
      $form_state->set('num_options', $num_options);
    }

    $options = $this->getEntityOptions();

    for ($i = 0; $i < $num_options; $i++) {
      $form['options']['options'][$i] = [
        '#type' => 'textfield',
        '#placeholder' => $this->t('Option (leave empty to remove)'),
        '#default_value' => $options[$i] ?? '',
        '#states' => [
          'enabled' => [
            ':input[name="type"]' => [
              ['value' => 'multi-choice'],
              ['value' => 'checkboxes'],
            ],
          ],
        ],
      ];
    }

    return $form;
  }

  /**
   * Fills additional entity data if correct type is selected
   *
   * @param  array              $form
   *   Array with form structure
   * @param  FormStateInterface $form_state
   *   Form state object
   */
  private function fillEntityData(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $type = $form_state->getValue('type')[0]['value'] ?? '';

    if ($type === 'scale') {
      $entity->set('data', [
        'range' => [
          'min' => $form_state->getValue('range_min'),
          'max' => $form_state->getValue('range_max'),
        ],
      ]);
    } else if ($type === 'multi-choice' || $type === 'checkboxes') {
      $data = [
        'options' => [],
      ];

      foreach ($form_state->getValue('options') as $option) {
        if (!empty(trim($option))) {
          $data['options'][] = $option;
        }
      }

      $entity->set('data', $data);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $this->fillEntityData($form, $form_state);
    $status = parent::save($form, $form_state);

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label LaPills Question Entity.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label LaPills Question Entity.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.la_pills_question_entity.canonical', ['la_pills_question_entity' => $entity->id()]);
  }

  /**
   * Builds a table row renderable for an Entity
   *
   * @param  LaPillsQuestionEntityInterface $entity
   *   LaPillsQuestionEntity instance
   * @return array
   *   Renderable for entity table row
   */
  private function buildTableRow(LaPillsQuestionEntityInterface $entity) {
    $link_options = [
      'attributes' => [
        'class' => ['use-ajax',],
        'data-dialog-type' => 'modal',
      ],
    ];
    $edit_link_options = $link_options;
    $edit_link_options['attributes']['class'][] = 'btn';
    $edit_link_options['attributes']['class'][] = 'btn-success';
    $edit_link_options['attributes']['title'] = $this->t('Edit');
    $edit_link_options['attributes']['data-toggle'] = 'tooltip';

    $remove_link_options = $link_options;
    $remove_link_options['attributes']['class'][] = 'btn';
    $remove_link_options['attributes']['class'][] = 'btn-danger';
    $remove_link_options['attributes']['title'] = $this->t('Remove');
    $remove_link_options['attributes']['data-toggle'] = 'tooltip';

    $renderable = [
      '#type' => 'html_tag',
      '#tag' => 'tr',
      '#attributes' => [
        'id' => 'quick-feedback-item-' . $entity->id(),
      ],
    ];
    $renderable['icon'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['icon']['icon'] = [
      '#type' => 'markup',
      '#markup' => '<i class="' . $entity->get('icon')->value . '"></i>'
    ];
    $renderable['short_name'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['short_name']['sort_name'] = $entity->short_name->view(['label' => 'hidden',]);
    $renderable['prompt'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['prompt']['prmpt'] = $entity->prompt->view(['label' => 'hidden',]);
    $renderable['type'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['type']['type'] = $entity->short_name->view(['label' => 'hidden',]);
    $renderable['active'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['active']['active'] = [
      '#type' => 'checkbox',
      '#attributes' => [
        'title' => $this->t('Mark question as active'),
        'data-toggle' => 'tooltip',
      ],
    ];
    $renderable['actions'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['actions']['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['btn-group', 'btn-group-sm',],
        'role' => 'group',
        'aria-label' => $this->t('Actions'),
      ],
    ];
    $renderable['actions']['actions']['edit'] = Link::createFromRoute(Markup::create('<i class="fas fa-edit"></i>'), 'entity.la_pills_question_entity.edit_form', ['la_pills_question_entity' => $entity->id(),], $edit_link_options)->toRenderable();
    $renderable['actions']['actions']['remove'] = Link::createFromRoute(Markup::create('<i class="fas fa-trash"></i>'), 'entity.la_pills_question_entity.delete_form', ['la_pills_question_entity' => $entity->id(),], $remove_link_options)->toRenderable();

    return $renderable;
  }

  /**
   * Handled form submit in case of AJAX
   *
   * @param  array              $form
   *   Array with form structure
   * @param  FormStateInterface $form_state
   *   Form state instance
   * @return AjaxResponse
   *   AJAX response with multiple commands
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $response = new AjaxResponse();

    $messages = \Drupal::messenger()->all();

    if (isset($messages['error']) && count($messages['error']) > 0) {
      $response->addCommand(new ReplaceCommand('.la-pills-question-entity-form', $form));

      $renderable = [
        '#theme' => 'status_messages',
        '#message_list' =>  $messages,
      ];

      $rendered = \Drupal::service('renderer')->render($renderable);
      \Drupal::messenger()->deleteAll();

      $response->addCommand(new HtmlCommand('#result-message', $rendered));

      return $response;
    }

    $this->fillEntityData($form, $form_state);
    $status = parent::save($form, $form_state);

    $response->addCommand(new CloseModalDialogCommand());

    switch ($status) {
      case SAVED_NEW:
        $row = $this->buildTableRow($entity);
        $response->addCommand(
          new PrependCommand(
            '#quick-feedback-items > tbody',
            render($row)
          )
        );
        // XXX This one does not remove a parent elemnt tr, but a child element td
        $response->addCommand(
          new RemoveCommand('#quick-feedback-items > tbody > tr > td.empty.message')
        );
        break;

      default:
        $row = $this->buildTableRow($entity);
        $response->addCommand(
          new ReplaceCommand(
            '#quick-feedback-item-' . $entity->id(),
            render($row)
          )
        );
    }

    $response->addCommand(new RestripeCommand('#quick-feedback-items'));

    return $response;
  }

  /**
   * Increments number of options by one
   *
   * @param array              $form
   *   Array with form strcuture
   * @param FormStateInterface $form_state
   *   Form state instance
   */
  public function addNewOption(array &$form, FormStateInterface $form_state) {
    $num_options = $form_state->get('num_options');
    $form_state->set('num_options', $num_options + 1);
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for adding a new option
   *
   * @param  array              $form
   *   Array with form structure
   * @param  FormStateInterface $form_state
   *   Form sate instance
   * @return array
   *   Form renderable part with options wrapper
   */
  public function ajaxAddNewOption(array &$form, FormStateInterface $form_state) {
    return $form['options']['options'];
  }

}
