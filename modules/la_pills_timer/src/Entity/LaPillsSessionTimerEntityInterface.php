<?php

namespace Drupal\la_pills_timer\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining LA Pills Timer entities.
 *
 * @ingroup la_pills_timer
 */
interface LaPillsSessionTimerEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the LA Pills Timer name.
   *
   * @return string
   *   Name of the LA Pills Timer.
   */
  public function getName();

  /**
   * Sets the LA Pills Timer name.
   *
   * @param string $name
   *   The LA Pills Timer name.
   *
   * @return \Drupal\la_pills_timer\Entity\LaPillsTimerEntityInterface
   *   The called LA Pills Timer entity.
   */
  public function setName($name);

  /**
   * Gets the LA Pills Timer creation timestamp.
   *
   * @return int
   *   Creation timestamp of the LA Pills Timer.
   */
  public function getCreatedTime();

  /**
   * Gets the LA Pills Timer status.
   *
   * @return bool
   */
  public function getStatus();

  /**
   * Sets the LA Pills Timer creation timestamp.
   *
   * @param int $timestamp
   *   The LA Pills Timer creation timestamp.
   *
   * @return \Drupal\la_pills_timer\Entity\LaPillsTimerEntityInterface
   *   The called LA Pills Timer entity.
   */
  public function setCreatedTime($timestamp);

  public function getSession();

  public function getSessionId();

  public function getTimerGroup();

  public function getSessionsIds();

  public function getDuration();

  public function startSession();

  public function stopSession();

  public function getCurrentDuration();

}
