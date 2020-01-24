<?php

namespace Drupal\la_pills_rest\Plugin\rest\resource;

use Drupal\rest\ModifiedResourceResponse;
use Drupal\rest\Plugin\ResourceBase;
use Drupal\rest\ResourceResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Provides a resource to get view modes by entity and bundle.
 *
 * @RestResource(
 *   id = "la_pills_user_sessions_resource",
 *   label = @Translation("LAPills User Sessions resource"),
 *   uri_paths = {
 *     "canonical" = "api/la_pills/user/{id}/sessions"
 *   }
 * )
 */
class LaPillsUserSessionsResource extends ResourceBase {

  /**
   * A current user instance.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Entity type manager instance.
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);
    $instance->logger = $container->get('logger.factory')->get('la_pills_rest');
    $instance->currentUser = $container->get('current_user');
    $instance->entityTypeManager = $container->get('entity_type.manager');
    return $instance;
  }

  /**
   * Responds to GET requests.
   *
   * @param int $id User identifier
   *
   * @return \Drupal\rest\ResourceResponse
   *   The HTTP response object.
   *
   * @throws \Symfony\Component\HttpKernel\Exception\HttpException
   *   Throws exception expected.
   */
  public function get(int $id) {
    // You must to implement the logic of your REST Resource here.
    // Use current user after pass authentication to validate access.
    // TODO See if permission checks should be different
    if (!$this->currentUser->hasPermission('access content')) {
      throw new AccessDeniedHttpException();
    }

    $ids = \Drupal::entityQuery('session_entity')
      ->condition('user_id', $id)
      ->sort('created', 'DESC')
      ->execute();

    $sessions = $this->entityTypeManager
      ->getStorage('session_entity')
      ->loadMultiple($ids);

    $data = [
      'sessions' => [],
    ];

    if ($sessions) {
      foreach ($sessions as $session) {
        // TODO It might make sense to optimize this one in case multiple
        // sessions use the same template
        $template = $session->getSessionTemplate();

        // Allow template data to be changed or extended
        $tdata = $template->getData();
        \Drupal::moduleHandler()->alter('la_pills_session_template_data', $tdata, $session);

        // TODO Consider moving into a standalone service and reusing for
        // multiple different resources
        $data['sessions'][] = [
          'id' => $session->id(),
          'name' => $session->getName(),
          'created' => $session->getCreatedTime(),
          'owner' => $session->getOwnerId(),
          'published' => $session->isPublished(),
          'active' => $session->isActive(),
          'allow_anonymous_responses' => $session->getAllowAnonymousResponses(),
          'template' => $tdata,
        ];
      }
    }

    $response = new ResourceResponse($data, 200);

    // TODO See if setting it non-cacheable is a better approach, see URL for details
    // https://drupal.stackexchange.com/a/224508/92770
    $response->addCacheableDependency($data);

    return $response;
  }

}
