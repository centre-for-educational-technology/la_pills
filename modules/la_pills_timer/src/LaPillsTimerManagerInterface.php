<?php

namespace Drupal\la_pills_timer;

use Drupal\la_pills\Entity\SessionEntity;


/**
 * Interface LaPillsTimerManagerInterface.
 */
interface LaPillsTimerManagerInterface {

  /**
   * Returns count of timers for data gathering session
   *
   * @param  \Drupal\la_pills\Entity\SessionEntity $entity
   *   Data gathering sesion object
   *
   * @return int
   *   Number of timers
   */
  public function getSessionEntityTimersCount(SessionEntity $entity) : int;

  /**
   * Returns count of active timers for data gathering session
   *
   * @param  \Drupal\la_pills\Entity\SessionEntity $entity
   *   Data gathering sesion object
   *
   * @return int
   *   Number of active timers
   */
  public function getSessionEntityActiveTimersCount(SessionEntity $entity) : int;

  /**
   * Returns timers for data gathering session
   *
   * @param  \Drupal\la_pills\Entity\SessionEntity $entity
   *   Data gathering sesion object
   *
   * @return array
   *   An array of timers
   */
  public function getSessionEntityTimers(SessionEntity $entity) : array;

  /**
   * Returns count of timers for current user
   *
   * @return int
   *   Number of timers
   */
  public function getCurrentUserTimerCount() : int;

  /**
   * Returns count of active timers for current user
   *
   * @return int
   *   Number of active timers
   */
  public function getCurrentUserActiveTimerCount() : int;

  /**
   * Returns timers for currrent user
   *
   * @return array
   *   An array of timers
   */
  public function getCurrentUserActiveTimers() : array;

  /**
   * Stops any active timers that belong to data gatherig session
   *
   * @param  \Drupal\la_pills\Entity\SessionEntity $entity
   *   Data gathering sesion object
   *
   * @return array
   *   An array of stopped
   */
  public function stopAllActiveTimers(SessionEntity $entity) : array;

  /**
   * Determines if current user can access the data gathering session timers page
   *
   * @param  \Drupal\la_pills\Entity\SessionEntity $entity
   *   Data gathering sesion object
   *
   * @return bool
   *   TRUE if user can, FALSE otherwise
   */
  public function canAccessSessionEntityTimersPage(SessionEntity $entity) : bool;

}
