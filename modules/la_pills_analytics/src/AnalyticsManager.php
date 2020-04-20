<?php

namespace Drupal\la_pills_analytics;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Controller\TitleResolverInterface;
use Symfony\Component\HttpFoundation\Request;

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

  private function getSessionId(Request $request) {
    // XXX Generates new session id for anonymous witheach request
    return $request->hasSession() ? $request->getSession()->getId() : NULL;
  }

  private function getName(Request $request) {
    if ($this->currentUser->isAnonymous() && $request->hasSession()) {
      // XXX Attribute name should not be hard-coded
      // XXX Trying to get the value from session object results in an error
      if (isset($_SESSION['_sf2_attributes'][LA_PILLS_NAME_KEY])) {
        return $_SESSION['_sf2_attributes'][LA_PILLS_NAME_KEY];
      }
    }

    return NULL;
  }

  private function getTitle(Request $request) {
    if ($route = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_OBJECT)) {
      return $this->titleResolver->getTitle($request, $route);
    }

    return NULL;
  }

  public function storeAction(string $type, Request $request, array $data = []) {
    $result = $this->database->insert('la_pills_analytics_actions')
    ->fields(['type', 'path', 'uri', 'title', 'session_id', 'user_id', 'name', 'data', 'created',])
    ->values([
      'type' => $type,
      'path' => $request->getPathInfo(),
      'uri' => $request->getRequestUri(),
      'title' => $this->getTitle($request),
      'session_id' => $this->getSessionId($request),
      'user_id' => $this->currentUser->isAuthenticated() ? $this->currentUser->id() : NULL,
      'name' => $this->getName($request),
      'data' => serialize($data),
      'created' => $this->time->getRequestTime(),
    ])
    ->execute();
  }

}
