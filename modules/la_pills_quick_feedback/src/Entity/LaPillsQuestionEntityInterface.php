<?php

namespace Drupal\la_pills_quick_feedback\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining LaPills Question Entity entities.
 *
 * @ingroup la_pills_quick_feedback
 */
interface LaPillsQuestionEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the LaPills Question Entity name.
   *
   * @return string
   *   Name of the LaPills Question Entity.
   */
  public function getName();

  /**
   * Sets the LaPills Question Entity name.
   *
   * @param string $name
   *   The LaPills Question Entity name.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setName($name);

  /**
   * Gets the LaPills Question Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the LaPills Question Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the LaPills Question Entity creation timestamp.
   *
   * @param int $timestamp
   *   The LaPills Question Entity creation timestamp.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setCreatedTime($timestamp);

}
