<?php

namespace Drupal\la_pills_quick_feedback\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountInterface;

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

  /**
   * Gets icon classes.
   *
   * @return string
   *   Icon classes
   */
  public function getIcon();

  /**
   * Sets the LaPills Question Entity icon classes.
   *
   * @param string $icon
   *   Icon classes.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setIcon($icon);

  /**
   * Gets short name.
   *
   * @return string
   *   Short name.
   */
  public function getShortName();

  /**
   * Sets short name.
   *
   * @param string $short_name
   *   Short name.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setShortName($short_name);

  /**
   * Gets prompt that is also used as label.
   *
   * @return string
   *   Prompt.
   */
  public function getPrompt();

  /**
   * Sets prompt that is also used as label.
   *
   * @param string $prompt
   *   Prompt.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setPrompt($prompt);

  /**
   * Gets description.
   *
   * @return string
   *   Description, a possibly multilined plain text.
   */
  public function getDescription();

  /**
   * Sets description.
   *
   * @param string $description
   *   Description.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setDescription($description);

  /**
   * Gets question type.
   *
   * @return string
   *   Question type.
   */
  public function getType();

  /**
   * Sets question type.
   *
   * @param string $type
   *   Question type.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   *
   * @throws \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionTypeException
   *   In case provided type is not one of the allowed ones.
   */
  public function setType($type);

  /**
   * Gets additional data that depends on question type. Used as a storage for
   * any additional field values that depend on selected question type. Example:
   * range key with an array of min and max values for scale.
   *
   * @return array
   *   Additional data.
   */
  public function getData();

  /**
   * Sets additional data.
   *
   * @param array $data
   *   Additional data.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface
   *   The called LaPills Question Entity entity.
   */
  public function setData(array $data);

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
   * Determines if curently logged in user has marked question as active. Allows
   * to bypass usage of static cache.
   *
   * @param  boolean $buypass_cache
   *   Determines if static cache should be bypassed, defaults to FALSE
   * @return boolean
   *   TRUE if active, FALSE if not
   */
  public function isActive(bool $buypass_cache = FALSE);

  /**
   * Returns data structure for the current question that could be used with
   * questionnaires. This closely resembles the structure found within Session
   * Template structures.
   *
   * @return array
   *   Question data strcuture usable with questionnaires.
   */
  public function getQuesionDataForQuestionnaire();

}
