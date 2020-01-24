<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\Core\Ajax\PrependCommand;
use Drupal\Core\Ajax\RestripeCommand;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\la_pills\Entity\SessionEntityInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\la_pills\RenderableHelper;

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

    $form['user_id']['#access'] = FALSE;

    if ($form_state->getBuildInfo()['form_id'] === 'session_entity_edit_form') {
      $form['template']['widget']['#disabled'] = TRUE;
      $form['code']['widget']['#disabled'] = TRUE;
    }

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
        $form['#title'] = $this->t('Create new data gathering session');
      } else {
        $form['#title'] = $this->t('Edit data gathering session');
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
        \Drupal::messenger()->addMessage($this->t('Created the %label data gathering session.', [
          '%label' => $entity->label(),
        ]));
        break;

      default:
        \Drupal::messenger()->addMessage($this->t('Saved the %label data gathering session.', [
          '%label' => $entity->label(),
        ]));
    }
    $form_state->setRedirect('entity.session_entity.canonical', ['session_entity' => $entity->id()]);
  }

  /**
   * Builds a table row renderable for an Entity
   *
   * @param  Drupal\la_pills\Entity\SessionEntityInterface $entity
   *   LaPillsQuestionEntity instance
   * @return array
   *   Renderable for entity table row
   */
  private function buildTableRow(SessionEntityInterface $entity) {
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
        'id' => 'session-entity-' . $entity->id(),
      ],
    ];
    $renderable['name'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['name']['name'] = Link::createFromRoute(
      $entity->label(),
      'entity.session_entity.canonical',
      ['session_entity' => $entity->id()]
    )->toRenderable();
    $renderable['session_template'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['session_template']['session_template'] = [
      '#plain_text' => $entity->getSessionTemplate()->getTitle(),
    ];
    $renderable['code'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['code']['code'] = [
      '#plain_text' => $entity->getCode(),
    ];
    $renderable['answers'] = [
      '#type' => 'html_tag',
      '#tag' => 'td',
    ];
    $renderable['answers']['answers'] = RenderableHelper::downloadAnswersLink($entity, ['btn-xs'])->toRenderable();
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
    $renderable['actions']['actions']['edit'] = Link::createFromRoute(Markup::create('<i class="fas fa-edit"></i>'), 'entity.session_entity.edit_form', ['session_entity' => $entity->id(),], $edit_link_options)->toRenderable();
    $renderable['actions']['actions']['remove'] = Link::createFromRoute(Markup::create('<i class="fas fa-trash"></i>'), 'entity.session_entity.delete_form', ['session_entity' => $entity->id(),], $remove_link_options)->toRenderable();

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
      $response->addCommand(new ReplaceCommand('.session-entity-form', $form));

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
            '#data-gathering-sessions > tbody',
            render($row)
          )
        );
        // XXX This one does not remove a parent elemnt tr, but a child element td
        $response->addCommand(
          new RemoveCommand('#data-gathering-sessions > tbody > tr > td.empty.message')
        );
        break;

      default:
        $row = $this->buildTableRow($entity);
        $response->addCommand(
          new ReplaceCommand(
            '#session-entity-' . $entity->id(),
            render($row)
          )
        );
    }

    $response->addCommand(new RestripeCommand('#data-gathering-sessions'));

    return $response;
  }

}
