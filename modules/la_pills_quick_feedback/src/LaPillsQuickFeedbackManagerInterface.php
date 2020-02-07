<?php

namespace Drupal\la_pills_quick_feedback;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;
use Drupal\la_pills\Entity\SessionEntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Interface LaPillsQuickFeedbackManagerInterface.
 */
interface LaPillsQuickFeedbackManagerInterface {

  /**
   * Returns allowed question types options.
   *
   * @param  boolean $translate
   *   Translate titles or not, defaults to TRUE.
   *
   * @return array
   *   An array with key => title question type options.
   */
  public function getQuestionTypes($translate = TRUE) : array;

  /**
   * Returns active questions for current user. Uses static cache.
   *
   * @return array
   *   An array of question identifiers
   */
  function getActiveQuestions($entities = FALSE) : array;

  /**
   * Returns count of active questions for current user. Uses getActiveQuestions
   * method that enforces static cache.
   *
   * @return int
   *   Count of active questions for current user
   */
  function getActiveQuestionsCount() : int;

  /**
   * Determines current user has marked a question as active. Can bypass usage
   * of static cache.
   *
   * @param  LaPillsQuestionEntityInterface $question
   *   Question entity
   * @param  boolean                        $bypass_cache
   *   Determines if static cache should be bypassed, defaults to FALSE
   *
   * @return boolean
   *   TRUE if active, FALSE if not
   */
  function isActiveQuestion(LaPillsQuestionEntityInterface $question, bool $bypass_cache = FALSE) : bool;

  /**
   * Returns count of questions for current user.
   *
   * @return int
   *   Number of question for current user.
   */
  public function getQuestionsCount() : int;

  /**
   * Returns Quick Feedback questionnaire for Session Entity, if any.
   *
   * @param  SessionEntityInterface $session_entity
   *   Session Entity instance.
   *
   * @return mixed
   *   Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntity or NULL
   */
  public function getQuickFeedbackQuestionnaire(SessionEntityInterface $session_entity);

  /**
   * Determines if Session Entity has a Quick Feedback questionnaire.
   *
   * @param  SessionEntityInterface $session_entity
   *   Session Entity instance.
   *
   * @return bool
   *   TRUE if exists and FALSE if not
   */
  public function hasQuickFeedbackQuestionnaire(SessionEntityInterface $session_entity) : bool;

  /**
   * Makes question active for provided user acount.
   *
   * @param LaPillsQuestionEntityInterface $question
   *   Question entity.
   * @param AccountProxy                   $account
   *   Account.
   */
  public function makeQuestionActive(LaPillsQuestionEntityInterface $question, AccountInterface $account) : void;

  /**
   * Makes question inactive for provided user account.
   *
   * @param LaPillsQuestionEntityInterface $question
   *   Question entity.
   * @param AccountProxy                   $account
   *   Account.
   */
  public function makeQuestionInactive(LaPillsQuestionEntityInterface $question, AccountInterface $account) : void;

}
