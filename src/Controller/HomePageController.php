<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Url;

/**
 * Class HomePageController.
 */
class HomePageController extends ControllerBase {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Constructs a new HomePageController object.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user')
    );
  }

  /**
   * Home page view.
   *
   * @return array
   *   Content structure.
   */
  public function view() {
    $response = [];

    $teacher_route = $this->currentUser->isAnonymous() ? 'user.login' : 'entity.session_entity.collection';

    $response['actions'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['home-page-actions'],
      ],
      '#attached' => [
        'library' => [
          'la_pills/home_page'
        ],
      ],
    ];
    $response['actions']['student'] = [
      '#type' => 'button',
      '#name' => 'student',
      '#value' => $this->t('I am a student'),
      '#attributes' => [
        'class' => ['btn', 'btn-success', 'btn-lg',],
        'data-url' => Url::fromRoute('la_pills.session_entity_code', [], ['absolute' => TRUE,])->toString(),
      ],
    ];
    $response['actions']['teacher'] = [
      '#type' => 'button',
      '#name' => 'teacher',
      '#value' => $this->t('I am a teacher'),
      '#attributes' => [
        'class' => ['btn', 'btn-primary', 'btn-lg',],
        'data-url' => Url::fromRoute($teacher_route, [], ['absolute' => TRUE,])->toString(),
      ],
    ];

    if ($this->currentUser->isAuthenticated() && !$this->currentUser->hasPermission('add la pills session entities')) {
      $response['actions']['teacher']['#attributes']['disabled'] = 'disabled';
    }

    return $response;
  }

}
