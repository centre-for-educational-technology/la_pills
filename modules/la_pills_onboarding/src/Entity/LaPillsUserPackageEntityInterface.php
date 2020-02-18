<?php

namespace Drupal\la_pills_onboarding\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

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

  public function getQuestionsEntities() : array;

  public function getQuestionsCount() : int;

  public function getActivitiesEntities() : array;

  public function getActivitiesCount() : int;

}
