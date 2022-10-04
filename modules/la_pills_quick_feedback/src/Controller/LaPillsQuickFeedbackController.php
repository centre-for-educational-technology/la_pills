<?php

namespace Drupal\la_pills_quick_feedback\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\la_pills_quick_feedback\LaPillsQuickFeedbackManagerInterface;

/**
 * Class LaPillsQuickFeedbackController.
 */
class LaPillsQuickFeedbackController extends ControllerBase {

  /**
   * @inheritdoc
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxy $currentUser, Connection $database, LaPillsQuickFeedbackManagerInterface $quick_feedback_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
    $this->database = $database;
    $this->quickFeedbackManager = $quick_feedback_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user'),
      $container->get('database'),
      $container->get('la_pills_quick_feedback.manager')
    );
  }

  /**
   * Returns renderable for active or inactive checkbox.
   *
   * @param  LaPillsQuestionEntityInterface $question
   *   Question entity
   * @return array
   *   An array with renderable structure
   */
  private function activeRenderable(LaPillsQuestionEntityInterface $question) {
    $renderable = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'question-' . $question->id() . '-active-inactive-wrapper',
      ],
    ];
    $renderable['active'] = [
      '#type' => 'checkbox',
      '#checked' => $question->isActive(),
      '#attributes' => [
        'title' => $this->t('Mark question as active'),
        'data-toggle' => 'tooltip',
        'class' => ['question-active-inactive'],
        'data-id' => $question->id(),
      ],
    ];

    return $renderable;
  }

  /**
   * Index.
   *
   * @return string
   *   Renderable page structure.
   */
  public function index() {
    $link_options = [
      'attributes' => [
        'class' => ['use-ajax',],
        'data-dialog-type' => 'modal',
      ],
    ];
    $create_link_options = $link_options;
    $create_link_options['attributes']['class'] = array_merge($create_link_options['attributes']['class'], ['btn', 'btn-success',]);
    $create_link_options['attributes']['title'] = $this->t('Create new Quick Feedback item');
    $create_link_options['attributes']['data-toggle'] = 'tooltip';

    $ids = \Drupal::entityQuery('la_pills_question_entity')
      ->condition('user_id', $this->currentUser->id())
      ->sort('id', 'DESC')
      ->execute();

    $questions = $this->entityTypeManager
      ->getStorage('la_pills_question_entity')
      ->loadMultiple($ids);

    if ($this->currentUser->hasPermission('add lapills question entity entities')) {
      $data['add'] = Link::createFromRoute(Markup::create('<i class="fas fa-plus"></i>'),'entity.la_pills_question_entity.add_form', [], $create_link_options)->toRenderable();
    }

    $data['questions'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Icon'),
        $this->t('Short name'),
        $this->t('Prompt'),
        $this->t('Type'),
        $this->t('Active'),
        $this->t('Actions'),
      ],
      '#empty' => $this->t('No quick feedback questions found'),
      '#attributes' => [
        'id' => 'quick-feedback-items',
      ],
      '#attached' => [
        'library' => [
          'la_pills_quick_feedback/question_active_inactive',
          'la_pills/fontawesome',
        ],
      ],
    ];

    if ($questions) {
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

      foreach($questions as $key => $question) {
        $data['questions'][$key]['#attributes']['id'] = 'quick-feedback-item-' . $question->id();
        $data['questions'][$key]['icon'] = [
          '#type' => 'markup',
          '#markup' => '<i class="' . $question->get('icon')->value . '"></i>'
        ];

        $data['questions'][$key]['short_name'] = $question->short_name->view(['label' => 'hidden',]);
        $data['questions'][$key]['prompt'] = $question->prompt->view(['label' => 'hidden',]);
        $data['questions'][$key]['type'] = $question->type->view(['label' => 'hidden',]);

        $data['questions'][$key]['active'] = $this->activeRenderable($question);

        $data['questions'][$key]['actions'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['btn-group', 'btn-group-sm',],
            'role' => 'group',
            'aria-label' => $this->t('Actions'),
          ],
        ];
        $data['questions'][$key]['actions']['preview'] = Link::createFromRoute(Markup::create('<i class="fas fa-eye"></i>'), 'entity.la_pills_question_entity.canonical', ['la_pills_question_entity' => $question->id(),], $preview_link_options)->toRenderable();
        $data['questions'][$key]['actions']['edit'] = Link::createFromRoute(Markup::create('<i class="fas fa-edit"></i>'), 'entity.la_pills_question_entity.edit_form', ['la_pills_question_entity' => $question->id(),], $edit_link_options)->toRenderable();
        $data['questions'][$key]['actions']['remove'] = Link::createFromRoute(Markup::create('<i class="fas fa-trash"></i>'), 'entity.la_pills_question_entity.delete_form', ['la_pills_question_entity' => $question->id(),], $remove_link_options)->toRenderable();
      }
    }

    return $data;
  }

  /**
   * AJAX callback to make a question active or inactive for current user.
   *
   * @param  LaPillsQuestionEntityInterface $question
   *   Question entity
   * @return AjaxResponse
   *   AjaxRespone with actions
   */
  public function ajaxQuestionActiveInactive(LaPillsQuestionEntityInterface $question) {
    $manager = \Drupal::service('la_pills_quick_feedback.manager');
    $response = new AjaxResponse();

    if ($question->isActive()) {
      $this->quickFeedbackManager->makeQuestionInactive($question, $this->currentUser);
    } else {
      $this->quickFeedbackManager->makeQuestionActive($question, $this->currentUser);
    }

    $renderable = $this->activeRenderable($question);
    // Override #checked value with active value fetched fresh from the database,
    // bypassing any caches
    $renderable['active']['#checked'] = $question->isActive(TRUE);

    $response->addCommand(
      new ReplaceCommand(
        '#question-' . $question->id() . '-active-inactive-wrapper',
        render($renderable)
      )
    );

    return $response;
  }

}
