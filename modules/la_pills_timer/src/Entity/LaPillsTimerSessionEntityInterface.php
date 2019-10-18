<?php

namespace Drupal\la_pills_timer\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining La Pills Timer Session entities.
 *
 * @ingroup la_pills_timer
 */
interface LaPillsTimerSessionEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the La Pills Timer Session creation timestamp.
   *
   * @return int
   */
  public function getStartTime();

  /**
   * Gets the La Pills Timer Session stop timestamp.
   *
   * @return int
   */
  public function getStopTime();

  /**
   * Gets the La Pills Timer Session duration time.
   *
   * @return int
   */
  public function getDuration();

  /**
   * Gets the La Pills Timer Session status.
   *
   * @return boolean
   */
  public function isActive();

  /**
   * Stops current session. Sets end time and duration.
   *
   * @return $this
   *   Timer session
   */
  public function stopSession();

  /**
   * Calculates duration between start time and provided stop timestamp value.
   *
   * @param  int $stop
   *   Stop timestamp in seconds
   *
   * @return int
   *   Duration
   */
  public function calculateDuration($stop = NULL);

}
