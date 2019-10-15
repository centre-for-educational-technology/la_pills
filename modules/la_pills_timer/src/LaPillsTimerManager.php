<?php

namespace Drupal\la_pills_timer;
use Drupal\webprofiler\Entity\EntityManagerWrapper;
use Drupal\Core\Session\AccountInterface;
use Drupal\la_pills\Entity\SessionEntity;

/**
 * Class LaPillsTimerManager.
 */
class LaPillsTimerManager implements LaPillsTimerManagerInterface {

  /**
   * Drupal\webprofiler\Entity\EntityManagerWrapper definition.
   *
   * @var \Drupal\webprofiler\Entity\EntityManagerWrapper
   */
  protected $entityTypeManager;

  protected $currentUser;

  /**
   * Constructs a new LaPillsTimerManager object.
   */
  public function __construct(EntityManagerWrapper $entity_type_manager, AccountInterface $current_user) {
    $this->entityTypeManager = $entity_type_manager;
    $this->currentUser = $current_user;
  }

  public function getSessionEntityTimersCount(SessionEntity $entity) : int {
    $query_session_timers = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $entity->id())
      ->sort('created', 'DESC');

    return $query_session_timers->count()->execute();
  }

  public function getSessionEntityTimers(SessionEntity $entity) : array {
    $query = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $entity->id());

    return\Drupal::entityTypeManager()
      ->getStorage('la_pills_session_timer_entity')
      ->loadMultiple($query->execute());
  }

  public function getCurrentUserTimerCount() : int {
    $query_timers = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id());

    return $query_timers->count()->execute();
  }

  public function getCurrentUserActiveTimerCount() : int {
    $query_active_timers = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', \Drupal::currentUser()->id())
      ->condition('status', TRUE);
    return $query_active_timers->count()->execute();
  }

  public function getCurrentUserActiveTimers() {
    $query = \Drupal::entityQuery('la_pills_timer_entity')
      ->condition('user_id', $this->currentUser->id())
      ->condition('status', TRUE)
      ->sort('created', 'DESC');

    return $this->entityTypeManager
      ->getStorage('la_pills_timer_entity')
      ->loadMultiple($query->execute());
  }

  public function stopAllActiveTimers(SessionEntity $entity) : array {
    $query = \Drupal::entityQuery('la_pills_session_timer_entity')
      ->condition('session_id', $entity->id())
      ->condition('status', TRUE)
      ->sort('created', 'DESC');
    $active_timers_ids = $query->execute();

    if ($active_timers_ids) {
      $timers = \Drupal::entityTypeManager()
        ->getStorage('la_pills_session_timer_entity')
        ->loadMultiple($active_timers_ids);

      foreach ($timers as $timer) {
        $timer_id = $timer->id();
        $timer->stopSession();
        $timer->save();
      }

      return $timers;
    }

    return [];
  }

}
