<?php

namespace Drupal\la_pills_onboarding\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface for defining User package entities.
 *
 * @ingroup la_pills_onboarding
 */
interface LaPillsUserPackageEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the User package name.
   *
   * @return string
   *   Name of the User package.
   */
  public function getName();

  /**
   * Sets the User package name.
   *
   * @param string $name
   *   The User package name.
   *
   * @return \Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntityInterface
   *   The called User package entity.
   */
  public function setName($name);

  /**
   * Gets the User package creation timestamp.
   *
   * @return int
   *   Creation timestamp of the User package.
   */
  public function getCreatedTime();

  /**
   * Sets the User package creation timestamp.
   *
   * @param int $timestamp
   *   The User package creation timestamp.
   *
   * @return \Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntityInterface
   *   The called User package entity.
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
   * Returns all linked questions
   *
   * @return array
   *   An array of LaPillsQuestionEntity objects
   */
  public function getQuestionsEntities() : array;

  /**
   * Returns count of liked questions
   *
   * @return int
   *   Linked questions count
   */
  public function getQuestionsCount() : int;

  /**
   * Returns all linked activities
   *
   * @return array
   *   An array of LaPillsTimerEntity objects
   */
  public function getActivitiesEntities() : array;

  /**
   * Returns count of all linked activities
   *
   * @return int
   *   Linked activities count
   */
  public function getActivitiesCount() : int;

}
