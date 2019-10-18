<?php

namespace Drupal\la_pills_timer\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for defining LA Pills Timer entities.
 *
 * @ingroup la_pills_timer
 */
interface LaPillsTimerEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

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

  /**
   * Determines if provided account is an owner of this timer
   *
   * @param  AccountInterface $account
   *   User account
   *
   * @return boolean
   *   TRUE if is an owner, FALSE otherwise
   */
  public function isOwner(AccountInterface $account);

  /**
   * Returns timer grouping
   *
   * @return string
   *   Timer group value
   */
  public function getTimerGroup();

}
