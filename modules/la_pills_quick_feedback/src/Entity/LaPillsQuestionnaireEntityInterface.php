<?php

namespace Drupal\la_pills_quick_feedback\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\Core\Entity\EntityPublishedInterface;
use Drupal\user\EntityOwnerInterface;

/**
 * Provides an interface for defining LaPills Questionnaire Entity entities.
 *
 * @ingroup la_pills_quick_feedback
 */
interface LaPillsQuestionnaireEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityPublishedInterface, EntityOwnerInterface {

  /**
   * Add get/set methods for your configuration properties here.
   */

  /**
   * Gets the LaPills Questionnaire Entity name.
   *
   * @return string
   *   Name of the LaPills Questionnaire Entity.
   */
  public function getName();

  /**
   * Sets the LaPills Questionnaire Entity name.
   *
   * @param string $name
   *   The LaPills Questionnaire Entity name.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntityInterface
   *   The called LaPills Questionnaire Entity entity.
   */
  public function setName($name);

  /**
   * Gets the LaPills Questionnaire Entity creation timestamp.
   *
   * @return int
   *   Creation timestamp of the LaPills Questionnaire Entity.
   */
  public function getCreatedTime();

  /**
   * Sets the LaPills Questionnaire Entity creation timestamp.
   *
   * @param int $timestamp
   *   The LaPills Questionnaire Entity creation timestamp.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntityInterface
   *   The called LaPills Questionnaire Entity entity.
   */
  public function setCreatedTime($timestamp);

}
