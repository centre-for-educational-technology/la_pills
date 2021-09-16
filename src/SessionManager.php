<?php


namespace Drupal\la_pills;

use Drupal\Component\Utility\Crypt;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class SessionManager implements SessionManagerInterface
{
  const SESSION_ID_KEY = 'la_pills.private.session.id';

  /**
   * @var Session
   */
  protected $session;

  /**
   * Creates service instance
   *
   * @param SessionInterface $session
   */
  public function __construct(SessionInterface $session) {
    $this->session = $session;
  }

  /**
   * {@inheritdoc}
   */
  public function hasSessionId(): bool
  {
    return $this->session->has(self::SESSION_ID_KEY);
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId(): string
  {
    if (!$this->hasSessionId()) {
      $this->session->set(self::SESSION_ID_KEY, Crypt::randomBytesBase64(64));
    }

    return $this->session->get(self::SESSION_ID_KEY);
  }
}
