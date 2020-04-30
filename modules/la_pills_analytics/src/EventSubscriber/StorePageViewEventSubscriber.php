<?php

namespace Drupal\la_pills_analytics\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\la_pills_analytics\AnalyticsManagerInterface;

/**
 * Class StorePageViewEventSubscriber.
 */
class StorePageViewEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\la_pills_analytics\AnalyticsManagerInterface instance.
   *
   * @var \Drupal\la_pills_analytics\AnalyticsManagerInterface
   */
  protected $analyticsManager;

  /**
   * Constructs a new StorePageViewEventSubscriber object.
   */
  public function __construct(AccountProxyInterface $current_user, AnalyticsManagerInterface $analytics_manager) {
    $this->currentUser = $current_user;
    $this->analyticsManager = $analytics_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events['kernel.finish_request'] = ['onKernelFinishRequest'];
    $events['kernel.terminate'] = ['onKernelTerminate'];

    return $events;
  }

  /**
   * Returns routes to be caputed as view actions.
   *
   * @return array
   *  Array of routes
   */
  private function getViewRoutes() : array {
    return [
      'la_pills.home_page_controller_view',
      'la_pills.session_entity_code',
      'entity.session_entity.collection',
      'entity.session_entity.mine',
      'entity.session_entity.add_form',
      'entity.session_entity.edit_form',
      'entity.session_entity.delete_form',
      'entity.session_entity.canonical',
      'entity.session_entity.dashboard',
      'entity.session_entity.questionnaire', // TODO It might make sense to set page title to questionnaire title
      'la_pills_timer.la_pills_timer_controller_sessionEntityTimers',
      'la_pills_quick_feedback.session_entity_quick_feedback_form',
      'la_pills_timer.la_pills_timer_controller_timers',
      'la_pills_timer.la_pills_timer_controller_addTimer',
      'la_pills_timer.la_pills_timer_controller_editTimer',
      'la_pills_timer.la_pills_timer_controller_removeTimer',
      'entity.la_pills_timer_entity.collection',
      'entity.la_pills_timer_entity.add_form',
      'entity.la_pills_timer_entity.edit_form',
      'entity.la_pills_timer_entity.delete_form',
      'entity.la_pills_timer_entity.canonical',
      'la_pills_quick_feedback.la_pills_quick_feedback_controller_index',
      'entity.la_pills_question_entity.collection',
      'entity.la_pills_question_entity.add_form',
      'entity.la_pills_question_entity.edit_form',
      'entity.la_pills_question_entity.delete_form',
      'entity.la_pills_question_entity.canonical',
      'la_pills_onboarding.la_pills_user_package_controller_mine',
      'entity.la_pills_user_package.collection',
      'entity.la_pills_user_package.add_form',
      'entity.la_pills_user_package.edit_form',
      'entity.la_pills_user_package.delete_form',
      'entity.la_pills_user_package.canonical',
      'session_template.preview',
      'la_pills.session_template_upload',
      'session_templates.manage',
      'session_template.delete',
    ];
  }

  /**
   * Returns views to be captured as general actions.
   *
   * @return array
   *   Array of routes
   */
  private function getActionRoutes() : array {
    return [
      'session_entity.close',
      'session_entity.download_answers',
      'la_pills_timer.la_pills_timer_controller_sessionTimer',
      'la_pills_timer.la_pills_timer_controller_stopAll',
      'la_pills_timer.la_pills_timer_controller_exportTimers', // This produces two events one of those AJAX preflight and the other one POST download event if that is possible
      'la_pills_timer.la_pills_timer_controller_ajaxTimerActiveInactive',
      'la_pills_quick_feedback.la_pills_quick_feedback_controller_ajaxQuestionActiveInactive',
    ];
  }

  /**
   * This method is called when the kernel.finish_request is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function onKernelFinishRequest(Event $event) {
    $session_key_name = 'la_pills_analytics_force_anonymous_session';
    $request = $event->getRequest();

    // Start session for anonymous user if not present
    if ($this->currentUser->isAnonymous() && !$request->getSession()) {
      \Drupal::service('session_manager')->start();
    }

    // A hacky way to enforce persistent session identifier for anonymous users
    // Identifier is constantly regenerated if session does not have some data
    // stored in it
    if ($this->currentUser->isAnonymous() && !$request->getSession()->has($session_key_name)) {
      $request->getSession()->set($session_key_name, 'anonymous');
    }
  }

  /**
   * This method is called when the kernel.terminate is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function onKernelTerminate(Event $event) {
    $view_routes = $this->getViewRoutes();
    $action_routes = $this->getActionRoutes();

    $request = $event->getRequest();
    $route_name = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME);

    if (in_array($route_name, $view_routes) && ($request->isMethod('GET') || ($request->isXmlHttpRequest() && $request->query->has('_wrapper_format') && $request->query->get('_wrapper_format') === 'drupal_modal'))) {
      $this->analyticsManager->storeView($request);
    }

    if (in_array($route_name, $action_routes)) {
      // TODO Need a better name instead of 'action'
      $this->analyticsManager->storeAction('action', $request);
    }
  }

}
