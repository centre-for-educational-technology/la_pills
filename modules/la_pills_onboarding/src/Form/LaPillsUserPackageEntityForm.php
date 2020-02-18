<?php

namespace Drupal\la_pills_onboarding\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Form controller for User package edit forms.
 *
 * @ingroup la_pills_onboarding
 */
class LaPillsUserPackageEntityForm extends ContentEntityForm {

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $account;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    // Instantiates this form class.
    $instance = parent::create($container);
    $instance->account = $container->get('current_user');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    /* @var \Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntity $entity */
    $form = parent::buildForm($form, $form_state);

    $form['user_id']['#access'] = FALSE;

    if ($this->getRequest()->isXmlHttpRequest()) {
      $form['ajax_messages'] = [
        '#type' => 'container',
        '#attributes' => [
          'id' => 'result-message',
        ],
        '#weight' => -50,
      ];

      $form['actions']['submit']['#ajax'] = [
        'callback' => '::ajaxSave',
      ];

      if (isset($form['actions']['delete'])) {
        $form['actions']['delete']['#access'] = FALSE;
      }

      if ($this->entity->isNew()) {
        $form['#title'] = $this->t('Create new User Package');
      } else {
        $form['#title'] = $this->t('Edit User Package');
      }
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $status = parent::save($form, $form_state);

    if ($this->getRequest()->isXmlHttpRequest()) {
      $form_state->set('savedEntityStatus', $status);
      return;
    }

    switch ($status) {
      case SAVED_NEW:
        $this->messenger()->addMessage($this->t('Created the %label User package.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        $this->messenger()->addMessage($this->t('Saved the %label User package.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.la_pills_user_package.canonical', ['la_pills_user_package' => $entity->id()]);
  }

  /**
   * Builds a table row renderable for an Entity
   *
   * @param  Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntityInterface $entity
   *   LaPillsUserPackageEntity instance
   * @return array
   *   Renderable for entity table row
   */
  private function buildTableRow(LaPillsUserPackageEntityInterface $entity) {
    $link_options = [
      'attributes' => [
        'class' => ['use-ajax',],
        'data-dialog-type' => 'modal',
      ],
    ];

    $preview_link_options = $link_options;
    $preview_link_options['attributes']['class'][] = 'btn';
    $preview_link_options['attributes']['class'][] = 'btn-info';
    $preview_link_options['attributes']['title'] = $this->t('Preview');
    $preview_link_options['attributes']['data-toggle'] = 'tooltip';

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
        'id' => 'user-package-' . $entity->id(),
      ],
    ];
    $renderable['name'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['name']['name'] = $entity->name->view(['label' => 'hidden',]);;
    $renderable['questions'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['questions']['questions'] = [
      '#plain_text' => $entity->getQuestionsCount(),
    ];
    $renderable['activities'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['activities']['activities'] = [
      '#plain_text' => $entity->getActivitiesCount(),
    ];
    $renderable['status'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['status']['status'] = [
      '#markup' => $entity->get('status')->value ? $this->t('Public') : $this->t('Private'),
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
    $renderable['actions']['actions']['preview'] = Link::createFromRoute(Markup::create('<i class="fas fa-eye"></i>'), 'entity.la_pills_user_package.canonical', ['la_pills_user_package' => $entity->id(),], $preview_link_options)->toRenderable();
    $renderable['actions']['actions']['edit'] = Link::createFromRoute(Markup::create('<i class="fas fa-edit"></i>'), 'entity.la_pills_user_package.edit_form', ['la_pills_user_package' => $entity->id(),], $edit_link_options)->toRenderable();
    $renderable['actions']['actions']['remove'] = Link::createFromRoute(Markup::create('<i class="fas fa-trash"></i>'), 'entity.la_pills_user_package.delete_form', ['la_pills_user_package' => $entity->id(),], $remove_link_options)->toRenderable();

    return $renderable;
  }

  /**
   * Handled form submit in case of AJAX
   *
   * @param  array              $form
   *   Array with form structure
   * @param  Drupal\Core\Form\FormStateInterface $form_state
   *   Form state instance
   *
   * @return Drupal\Core\Ajax\AjaxResponse
   *   AJAX response with multiple commands
   */
  public function ajaxSave(array &$form, FormStateInterface $form_state) {
    $entity = $this->entity;
    $response = new AjaxResponse();

    $messages = \Drupal::messenger()->all();

    if (isset($messages['error']) && count($messages['error']) > 0) {
      $response->addCommand(new ReplaceCommand('.la-pills-user-package-form', $form));

      $renderable = [
        '#theme' => 'status_messages',
        '#message_list' =>  $messages,
      ];

      $rendered = \Drupal::service('renderer')->render($renderable);
      \Drupal::messenger()->deleteAll();

      $response->addCommand(new HtmlCommand('#result-message', $rendered));

      return $response;
    }

    $response->addCommand(new CloseModalDialogCommand());

    switch ($form_state->get('savedEntityStatus')) {
      case SAVED_NEW:
        $row = $this->buildTableRow($entity);
        $response->addCommand(
          new PrependCommand(
            '#user-packages > tbody',
            render($row)
          )
        );
        // XXX This one does not remove a parent elemnt tr, but a child element td
        $response->addCommand(
          new RemoveCommand('#user-packages > tbody > tr > td.empty.message')
        );
        break;

      default:
        $row = $this->buildTableRow($entity);
        $response->addCommand(
          new ReplaceCommand(
            '#user-package-' . $entity->id(),
            render($row)
          )
        );
    }

    $response->addCommand(new RestripeCommand('#user-packages'));

    return $response;
  }

}
