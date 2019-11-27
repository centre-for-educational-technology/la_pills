<?php

namespace Drupal\la_pills_quick_feedback;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;

/**
 * Interface LaPillsQuickFeedbackManagerInterface.
 */
interface LaPillsQuickFeedbackManagerInterface {

  /**
   * Returns active questions for current user. Uses static cache.
   *
   * @return array
   *   An array of question identifiers
   */
  function getActiveQuestions($entities = FALSE);

  /**
   * Returns count of active questions for current user. Uses getActiveQuestions
   * method that enforces static cache.
   *
   * @return boolean
   *   Count of active questions for current user
   */
  function getActiveQuestionsCount();

  /**
   * Determines current user has marked a question as active. Can bypass usage
   * of static cache.
   *
   * @param  LaPillsQuestionEntityInterface $question
   *   Question entity
   * @param  boolean                        $bypass_cache
   *   Determines if static cache should be bypassed, defaults to FALSE
   * @return boolean
   *   TRUE if active, FALSE if not
   */
  function isActiveQuestion(LaPillsQuestionEntityInterface $question, bool $bypass_cache = FALSE);

}
