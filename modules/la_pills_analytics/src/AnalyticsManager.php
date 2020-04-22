<?php

namespace Drupal\la_pills_analytics;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Entity\EntityInterface;

/**
 * Class AnalyticsManager.
 */
class AnalyticsManager implements AnalyticsManagerInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Drupal\Component\Datetime\TimeInterface definition.
   * @var \Drupal\Component\Datetime\TimeInterface
   */
  protected $time;

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Controller\TitleResolverInterface definition.
   * @var \Drupal\Core\Controller\TitleResolverInterface
   */
  protected $titleResolver;

  /**
   * Constructs a new AnalyticsManager object.
   */
  public function __construct(Connection $database, TimeInterface $time, AccountProxyInterface $current_user, TitleResolverInterface $title_resolver) {
    $this->database = $database;
    $this->time = $time;
    $this->currentUser = $current_user;
    $this->titleResolver = $title_resolver;
  }

  /**
   * Returns session identifier extracted from request or NULL.
   *
   * @param  Request $request
   *   Request object
   *
   * @return mixed
   *   Session identifier or NULL if missing
   */
  private function getSessionId(Request $request) : ?string {
    // XXX Generates new session id for anonymous witheach request
    return $request->hasSession() ? $request->getSession()->getId() : NULL;
  }

  /**
   * Returns name entered by anonymous user or NULL if not set.
   *
   * @param  Request $request
   *   Request object
   *
   * @return mixed
   *   Stored name value or NULL if not set
   */
  private function getName(Request $request) : ?string {
    if ($this->currentUser->isAnonymous() && $request->hasSession()) {
      // XXX Attribute name should not be hard-coded
      // XXX Trying to get the value from session object results in an error
      if (isset($_SESSION['_sf2_attributes'][LA_PILLS_NAME_KEY])) {
        return $_SESSION['_sf2_attributes'][LA_PILLS_NAME_KEY];
      }
    }

    return NULL;
  }

  /**
   * Returns title from request or NULL if not set.
   * Any HTML tags are stripped.
   *
   * @param  Request $request
   *   Request object
   *
   * @return mixed
   *   Title or NULL if not set
   */
  private function getTitle(Request $request) : ?string {
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      return strip_tags($this->titleResolver->getTitle($request, $route));
    }

    return NULL;
  }

  public function getEntityData(EntityInterface $entity) {
    return [
      'type' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
      'uuid' => $entity->uuid(),
      'title' => $entity->label(),
    ];
  }

  private function storeActionRaw(array $values) {
    // TODO See if we need to have the try/catch block present
    if (!isset($values['user_id'])) {
      $values['user_id'] = $this->currentUser->isAuthenticated() ? $this->currentUser->id() : NULL;
    }

    if (!isset($values['created'])) {
      $values['created'] = $this->time->getRequestTime();
    }

    $this->database->insert('la_pills_analytics_action')
    ->fields(['type', 'path', 'uri', 'title', 'session_id', 'user_id', 'name', 'data', 'created',])
    ->values($values)
    ->execute();
  }

  /**
   * Stores action in the database. Some values are automatically extracted from
   * the provided request object.
   *
   * @param  string  $type
   *   Action type
   * @param  Request $request
   *   Request object
   * @param  array   $data
   *   Additional data for serialized column
   */
  public function storeAction(string $type, Request $request, array $data = []) {
    $this->storeActionRaw([
      'type' => $type,
      'path' => $request->getPathInfo(),
      'uri' => $request->getRequestUri(),
      'title' => $this->getTitle($request),
      'session_id' => $this->getSessionId($request),
      'name' => $this->getName($request),
      'data' => empty($data) ? NULL : serialize($data),
    ]);
  }

  public function storeView(Request $request, array $data = []) {
    $this->storeAction('view', $request, $data);
  }

  public function storeEntityAction(EntityInterface $entity, string $type, Request $request) {
    $this->storeActionRaw([
      'type' => $type,
      'path' => '/' . $entity->toUrl()->getInternalPath(),
      'uri' => $request->getRequestUri(),
      'title' => $entity->label(),
      'session_id' => $this->getSessionId($request),
      'name' => $this->getName($request),
      'data' => serialize([
        'entity' => $this->getEntityData($entity),
      ]),
    ]);
  }

}
