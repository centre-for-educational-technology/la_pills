<?php

namespace Drupal\la_pills_analytics\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountProxyInterface;

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
   * Constructs a new StorePageViewEventSubscriber object.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    //$events['kernel.response'] = ['onKernelResponse'];
    //$events['kernel.finish_request'] = ['onKernelFinishRequest'];
    $events['kernel.terminate'] = ['onKernelTerminate'];

    return $events;
  }

  /**
   * This method is called when the kernel.response is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function onKernelResponse(Event $event) {
    $request = $event->getRequest();

    //error_log('onKernelResponse');
    //error_log($request->attributes->get('_route'));
    //sleep(10);
    //\Drupal::messenger()->addMessage('Event kernel.response thrown by Subscriber in module la_pills_analytics.', 'status', TRUE);
  }

  /**
   * This method is called when the kernel.finish_request is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function onKernelFinishRequest(Event $event) {
    $request = $event->getRequest();

    //error_log('onKernelFinishRequest');
    //error_log($request->attributes->get('_route'));
    //sleep(10);
    //\Drupal::messenger()->addMessage('Event kernel.finish_request thrown by Subscriber in module la_pills_analytics.', 'status', TRUE);
  }

  /**
   * This method is called when the kernel.terminate is dispatched.
   *
   * @param \Symfony\Component\EventDispatcher\Event $event
   *   The dispatched event.
   */
  public function onKernelTerminate(Event $event) {
    $manager = \Drupal::service('la_pills_analytics.manager');
    $view_routes = [
      'la_pills.home_page_controller_view',
      'la_pills.session_entity_code',
      'entity.session_entity.collection',
      'entity.session_entity.mine',
      'entity.session_entity.add_form',
      'entity.session_entity.edit_form',
      'entity.session_entity.delete_form',
      'entity.session_entity.canonical',
      'entity.session_entity.dashboard',
      'entity.session_entity.questionnaire',
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
      'entity.la_pills_question_entity.add_form',
      'entity.la_pills_question_entity.edit_form',
      'entity.la_pills_question_entity.delete_form',
      'entity.la_pills_question_entity.canonical',
      'la_pills_onboarding.la_pills_user_package_controller_mine',
      'entity.la_pills_user_package.add_form',
      'entity.la_pills_user_package.edit_form',
      'entity.la_pills_user_package.delete_form',
      'entity.la_pills_user_package.canonical',
      'session_template.preview',
      'la_pills.session_template_upload',
      'session_templates.manage',
      'session_template.delete',
    ];
    $action_routes = [
      'session_entity.close',
      'session_entity.download_answers',
      'la_pills_timer.la_pills_timer_controller_sessionTimer',
      'la_pills_timer.la_pills_timer_controller_stopAll',
      'la_pills_timer.la_pills_timer_controller_exportTimers',
      'la_pills_timer.la_pills_timer_controller_ajaxTimerActiveInactive',
      'la_pills_quick_feedback.la_pills_quick_feedback_controller_ajaxQuestionActiveInactive',
    ];
    //TODO Make sure that session template actions are handled (upload and delete)
    $request = $event->getRequest();
    $route_name = $request->attributes->get(\Symfony\Cmf\Component\Routing\RouteObjectInterface::ROUTE_NAME);

    // TODO Consider only allowing the GET requests + AJAX form views (not form submissions)
    // Current solution would also capture form submissions and other actions
    // Possible solution is to check for _wrapper_format=drupal_ajax and ajax_form=1
    if (in_array($route_name, $view_routes)) {
      $manager->storeView($request);
    }

    if (in_array($route_name, $action_routes)) {
      // TODO Need a better name instead of 'action'
      $manager->storeAction('action', $request);
    }
  }

}
