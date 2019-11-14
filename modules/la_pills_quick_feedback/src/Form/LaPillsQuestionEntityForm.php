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
use Drupal\views\Ajax\ScrollTopCommand;
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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntity $entity */
    $form = parent::buildForm($form, $form_state);

    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxSave',
      ];
      $form['actions']['submit']['#submit'] = [];

      if (isset($form['actions']['delete'])) {
        $form['actions']['delete']['#access'] = FALSE;
      }
      $form['ajax_messages'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'result-message',
        ],
        '#weight' => -50,
      ];
    }

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
      $response->addCommand(new ScrollTopCommand('#drupal-modal--dialog'));

      return $response;
    }

    $status = parent::save($form, $form_state);

    $response->addCommand(new CloseModalDialogCommand());

    switch ($status) {
      case SAVED_NEW:
        $response->addCommand(
          new PrependCommand(
            '#quick-feedback-items > tbody',
            render($this->buildTableRow($entity))
          )
        );
        break;

      default:
        $response->addCommand(
          new ReplaceCommand(
            '#quick-feedback-item-' . $entity->id(),
            render($this->buildTableRow($entity))
          )
        );
    }

    $response->addCommand(new RestripeCommand('#quick-feedback-items'));

    return $response;
  }

}
