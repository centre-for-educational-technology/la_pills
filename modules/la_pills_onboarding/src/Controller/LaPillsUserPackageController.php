<?php

namespace Drupal\la_pills_onboarding\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Link;
use Drupal\Core\Render\Markup;

/**
 * Class LaPillsUserPackageController.
 */
class LaPillsUserPackageController extends ControllerBase {

  /**
   * Drupal\webprofiler\Entity\EntityManagerWrapper definition.
   *
   * @var \Drupal\webprofiler\Entity\EntityManagerWrapper
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->entityTypeManager = $container->get('entity_type.manager');
    $instance->currentUser = $container->get('current_user');
    return $instance;
  }

  /**
   * Shows user packages for current user.
   *
   * @return array
   *   Page structure.
   */
  public function mine() {
    $link_options = [
      'attributes' => [
        'class' => ['use-ajax',],
        'data-dialog-type' => 'modal',
      ],
    ];
    $create_link_options = $link_options;
    $create_link_options['attributes']['class'] = array_merge($create_link_options['attributes']['class'], ['btn', 'btn-success',]);
    $create_link_options['attributes']['title'] = $this->t('Create new User Package');
    $create_link_options['attributes']['data-toggle'] = 'tooltip';

    $ids = \Drupal::entityQuery('la_pills_user_package')
      ->condition('user_id', $this->currentUser->id())
      ->sort('id', 'DESC')
      ->execute();

    $packages = $this->entityTypeManager
      ->getStorage('la_pills_user_package')
      ->loadMultiple($ids);

    if ($this->currentUser->hasPermission('add user package entities')) {
      $data['add'] = Link::createFromRoute(Markup::create('<i class="fas fa-plus"></i>'),'entity.la_pills_user_package.add_form', [], $create_link_options)->toRenderable();
    }

    $data['packages'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Name'),
        $this->t('Questions'),
        $this->t('Activities'),
        $this->t('Status'),
        $this->t('Actions'),
      ],
      '#empty' => $this->t('No user packages found'),
      '#attributes' => [
        'id' => 'user-packages',
      ],
      '#attached' => [
        'library' => [
          'la_pills/fontawesome',
        ],
      ],
    ];

    if ($packages) {
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

      foreach($packages as $key => $package) {
        $data['packages'][$key]['#attributes']['id'] = 'user-package-' . $package->id();
        $data['packages'][$key]['name'] = $package->name->view(['label' => 'hidden',]);
        $data['packages'][$key]['questions'] = [
          '#plain_text' => $package->getQuestionsCount(),
        ];
        $data['packages'][$key]['activities'] = [
          '#plain_text' => $package->getActivitiesCount(),
        ];
        $data['packages'][$key]['status'] = [
          '#markup' => $package->get('status')->value ? $this->t('Public') : $this->t('Private'),
        ];
        $data['packages'][$key]['actions'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['btn-group', 'btn-group-sm',],
            'role' => 'group',
            'aria-label' => $this->t('Actions'),
          ],
        ];
        $data['packages'][$key]['actions']['preview'] = Link::createFromRoute(Markup::create('<i class="fas fa-eye"></i>'), 'entity.la_pills_user_package.canonical', ['la_pills_user_package' => $package->id(),], $preview_link_options)->toRenderable();
        $data['packages'][$key]['actions']['edit'] = Link::createFromRoute(Markup::create('<i class="fas fa-edit"></i>'), 'entity.la_pills_user_package.edit_form', ['la_pills_user_package' => $package->id(),], $edit_link_options)->toRenderable();
        $data['packages'][$key]['actions']['remove'] = Link::createFromRoute(Markup::create('<i class="fas fa-trash"></i>'), 'entity.la_pills_user_package.delete_form', ['la_pills_user_package' => $package->id(),], $remove_link_options)->toRenderable();
      }
    }

    return $data;
  }

}
