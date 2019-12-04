<?php

namespace Drupal\la_pills\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\Event;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\StringTranslation\TranslationManager;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;
use \Symfony\Component\HttpFoundation\Cookie;

/**
 * Class SessionEntityEventSubscriber.
 */
class SessionEntityEventSubscriber implements EventSubscriberInterface {

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Messenger service.
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Translation Manager service.
   *
   * @var Drupal\Core\StringTranslation\TranslationManager
   */
  protected $translationManager;

  /**
   * Constructs a new SessionEntityEventSubscriber object.
   */
  public function __construct(AccountProxyInterface $current_user, Messenger $messenger, TranslationManager $translation_manager) {
    $this->currentUser = $current_user;
    $this->messenger = $messenger;
    $this->translationManager = $translation_manager;
  }

  /**
   * {@inheritdoc}
   */
  static function getSubscribedEvents() {
    $events['kernel.request'] = ['onRequest'];

    return $events;
  }

  /**
   * This method is called whenever the kernel.request event is
   * dispatched.
   *
   * @param GetResponseEvent $event
   */
  public function onRequest(Event $event) {
    $routes = ['entity.session_entity.canonical', 'entity.session_entity.dashboard', 'entity.session_entity.questionnaire'];
    \Drupal::moduleHandler()->alter('la_pills_session_entity_protected_routes', $routes);

    $request = $event->getRequest();

    if ($this->currentUser->isAnonymous() && in_array($request->attributes->get('_route'), $routes)) {
      $session_entity = $request->attributes->get('session_entity');
      if (!$session_entity->getAllowAnonymousResponses()) {
        $this->messenger->addMessage($this->translationManager->translate('Current session does not allow anonymous responses!'), 'warning');
        $response = new RedirectResponse(Url::fromRoute('user.login')->toString());
        $response->headers->setCookie(new Cookie('Drupal.la_pills.session_entity_redirect_to', $session_entity->id(), REQUEST_TIME + 3600));
        $event->setResponse($response);
        return;
      }
    }
  }

}
