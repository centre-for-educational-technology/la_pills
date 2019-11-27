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

  /**
   * Gets questions. The strcture of resembles the one of a Session Template
   * questionnaire questions with some additional keys. This is a copy from
   * active questions for the user during the creation of the questionnaire.
   *
   * @return array
   *   Questions data structure.
   */
  public function getQuestions();

  /**
   * Sets sets questions.
   *
   * @param array $data
   *   Questions data structure.
   *
   * @return \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntityInterface
   *   The called LaPills Questionnaire Entity entity.
   */
  public function setQuestions(array $data);

  /**
   * Returns count of questions.
   *
   * @return int
   *   Questions count.
   */
  public function getQuestionCount();

  /**
   * Gets Session Entity that questionnaire is related to.
   *
   * @return Drupal\la_pills\Entity\SessionEntityInterface
   *   Session Entity.
   */
  public function getSession();

  /**
   * Returns an identifier for the Session Entity that the questionnaire is
   * related to.
   *
   * @return int
   *   Session Entity unique identifier.
   */
  public function getSessionId();

}
