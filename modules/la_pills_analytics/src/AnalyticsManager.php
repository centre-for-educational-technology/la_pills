<?php

namespace Drupal\la_pills_analytics;
use Drupal\Core\Database\Connection;
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
   * Drupal\Core\Database\Connection definition.
   *
   * @var \Drupal\Core\Database\Connection
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
    // NB! EventSubscriber makes sure that session identifier for anonymous user
    // is set and persists across requests within same session
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
      // TODO It is not good to use _sf2_attributes in hard-coded way, the issue
      // is that accessing Symfony session data after page alreasyd been sent
      // results in an arror
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

  /**
   * Store action data in the database. User identifier and created columns will
   * be populated automatically.
   *
   * @param array $values
   *   An array of values for available columns
   */
  private function storeActionRaw(array $values) : void {
    // TODO See if we need to have the try/catch block present
    if (!isset($values['user_id'])) {
      $values['user_id'] = $this->currentUser->isAuthenticated() ? $this->currentUser->id() : NULL;
    }

    if (!isset($values['created'])) {
      $values['created'] = $this->time->getRequestTime();
    }

    if (isset($values['data']) && (is_array($values['data']) || is_object($values['data']))) {
      $values['data'] = json_encode($values['data']);
    }

    $this->database->insert('la_pills_analytics_action')
    ->fields(['type', 'path', 'uri', 'title', 'session_id', 'user_id', 'name', 'data', 'created',])
    ->values($values)
    ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getEntityData(EntityInterface $entity) : array {
    return [
      'type' => $entity->getEntityTypeId(),
      'id' => $entity->id(),
      'uuid' => $entity->uuid(),
      'title' => $entity->label(),
    ];
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
   *   Additional data for data column
   */
   /**
    * {@inheritdoc}
    */
  public function storeAction(string $type, Request $request, array $data = []) : void {
    $this->storeActionRaw([
      'type' => $type,
      'path' => $request->getPathInfo(),
      'uri' => $request->getRequestUri(),
      'title' => $this->getTitle($request),
      'session_id' => $this->getSessionId($request),
      'name' => $this->getName($request),
      'data' => empty($data) ? NULL : $data,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function storeView(Request $request, array $data = []) : void {
    $this->storeAction('view', $request, $data);
  }

  /**
   * {@inheritdoc}
   */
  public function storeEntityAction(EntityInterface $entity, string $type, Request $request, array $data = []) : void {
    $data['entity'] = $this->getEntityData($entity);

    $this->storeActionRaw([
      'type' => $type,
      'path' => '/' . $entity->toUrl()->getInternalPath(),
      'uri' => $request->getRequestUri(),
      'title' => $entity->label(),
      'session_id' => $this->getSessionId($request),
      'name' => $this->getName($request),
      'data' => $data,
    ]);
  }

}
