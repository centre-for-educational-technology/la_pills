<?php

namespace Drupal\la_pills_quick_feedback\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountProxy;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Class LaPillsQuickFeedbackController.
 */
class LaPillsQuickFeedbackController extends ControllerBase {

  /**
   * @inheritdoc
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, AccountProxy $currentUser) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $currentUser;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('current_user')
    );
  }

  /**
   * Index.
   *
   * @return string
   *   Return Hello string.
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
      ->sort('created', 'DESC')
      ->execute();

    $questions = $this->entityTypeManager
      ->getStorage('la_pills_question_entity')
      ->loadMultiple($ids);

    $data['add'] = Link::createFromRoute(Markup::create('<i class="fas fa-plus"></i>'),'entity.la_pills_question_entity.add_form', [], $create_link_options)->toRenderable();

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
      '#attributes' => [
        'id' => 'quick-feedback-items',
      ],
      '#attached' => [
        'library' => [
          'la_pills_quick_feedback/fontawesome'
        ],
      ],
    ];

    if ($questions) {
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

        $data['questions'][$key]['active'] = [
          '#type' => 'checkbox',
          '#attributes' => [
            'title' => $this->t('Mark question as active'),
            'data-toggle' => 'tooltip',
          ],
          '#ajax' => [
            'callback' => array($this, 'findUsers'),
            'event' => 'click',
            'progress' => [
              'type' => 'throbber',
              'message' => NULL,
            ],
          ],
        ];

        $data['questions'][$key]['actions'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['btn-group', 'btn-group-sm',],
            'role' => 'group',
            'aria-label' => $this->t('Actions'),
          ],
        ];
        $data['questions'][$key]['actions']['edit'] = Link::createFromRoute(Markup::create('<i class="fas fa-edit"></i>'), 'entity.la_pills_question_entity.edit_form', ['la_pills_question_entity' => $question->id(),], $edit_link_options)->toRenderable();
        $data['questions'][$key]['actions']['remove'] = Link::createFromRoute(Markup::create('<i class="fas fa-trash"></i>'), 'entity.la_pills_question_entity.delete_form', ['la_pills_question_entity' => $question->id(),], $remove_link_options)->toRenderable();
      }
    }

    return $data;
  }

  public function findUsers() {

  }

}
