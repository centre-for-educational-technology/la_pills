<?php

namespace Drupal\la_pills_quick_feedback;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;

/**
 * Class LaPillsQuestionManager.
 */
class LaPillsQuestionManager implements LaPillsQuestionManagerInterface {

  /**
   * User active questions table
   *
   * @var string
   */
  const USER_ACTIVE_QUESTION_TABLE = 'user_active_question';

  /**
   * Drupal\Core\Session\AccountProxyInterface definition.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * Constructs a new LaPillsQuestionManager object.
   */
  public function __construct(AccountProxyInterface $current_user, Connection $database) {
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveQuestions() {
    $data = &drupal_static(__METHOD__);

    if (!isset($data)) {
      $query = $this->database->select(self::USER_ACTIVE_QUESTION_TABLE, 'uaq');
      $query->fields('uaq', ['question_id',]);
      $query->condition('uaq.user_id', $this->currentUser->id());

      $data = $query->execute()->fetchCol();
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveQuestionsCount() {
    return count($this->getActiveQuestions());
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveQuestion(LaPillsQuestionEntityInterface $question, bool $bypass_cache = FALSE) {
    if ($bypass_cache) {
      $query = $this->database->select(self::USER_ACTIVE_QUESTION_TABLE, 'uaq');
      $query->condition('uaq.user_id', $this->currentUser->id());
      $query->condition('uaq.question_id', $question->id());

      return $query->countQuery()->execute()->fetchField() > 0;
    }

    return in_array($question->id(), $this->getActiveQuestions());
  }

}
