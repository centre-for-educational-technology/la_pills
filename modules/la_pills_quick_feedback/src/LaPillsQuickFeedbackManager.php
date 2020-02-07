<?php

namespace Drupal\la_pills_quick_feedback;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;
use Drupal\la_pills\Entity\SessionEntityInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Session\AccountInterface;

/**
 * Class LaPillsQuickFeedbackManager.
 */
class LaPillsQuickFeedbackManager implements LaPillsQuickFeedbackManagerInterface {

  use StringTranslationTrait;

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
   * Constructs a new LaPillsQuickFeedbackManager object.
   */
  public function __construct(AccountProxyInterface $current_user, Connection $database) {
    $this->currentUser = $current_user;
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestionTypes($translate = TRUE) : array {
    if ($translate) {
      return [
        'short-text' => $this->t('Short text'),
        'long-text' => $this->t('Long text'),
        'scale' => $this->t('Scale'),
        'multi-choice' => $this->t('Multi-choice'),
        'checkboxes' => $this->t('Checkboxes'),
      ];
    }

    return [
      'short-text' => 'Short text',
      'long-text' => 'Long text',
      'scale' => 'Scale',
      'multi-choice' => 'Multi-choice',
      'checkboxes' => 'Checkboxes',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveQuestions($entities = FALSE) : array {
    $data = &drupal_static(__METHOD__);

    if (!isset($data)) {
      $query = $this->database->select(self::USER_ACTIVE_QUESTION_TABLE, 'uaq');
      $query->fields('uaq', ['question_id',]);
      $query->condition('uaq.user_id', $this->currentUser->id());

      $data = $query->execute()->fetchCol();
    }

    if ($entities) {
      return \Drupal::entityTypeManager()
        ->getStorage('la_pills_question_entity')
        ->loadMultiple($data);
    }

    return $data;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveQuestionsCount() : int {
    return count($this->getActiveQuestions());
  }

  /**
   * {@inheritdoc}
   */
  public function isActiveQuestion(LaPillsQuestionEntityInterface $question, bool $bypass_cache = FALSE) : bool {
    if ($bypass_cache) {
      $query = $this->database->select(self::USER_ACTIVE_QUESTION_TABLE, 'uaq');
      $query->condition('uaq.user_id', $this->currentUser->id());
      $query->condition('uaq.question_id', $question->id());

      return $query->countQuery()->execute()->fetchField() > 0;
    }

    return in_array($question->id(), $this->getActiveQuestions());
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestionsCount() : int {
    $query_questions = \Drupal::entityQuery('la_pills_question_entity')
      ->condition('user_id', $this->currentUser->id());

    return $query_questions->count()->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuickFeedbackQuestionnaire(SessionEntityInterface $session_entity) {
    $id = \Drupal::entityQuery('la_pills_questionnaire_entity')
      ->condition('session_id', $session_entity->id())
      ->range(0, 1)
      ->execute();

    if (!count($id) > 0) {
      return NULL;
    }

    return \Drupal::entityTypeManager()
      ->getStorage('la_pills_questionnaire_entity')
      ->load(array_values($id)[0]);
  }

  /**
   * {@inheritdoc}
   */
  public function hasQuickFeedbackQuestionnaire(SessionEntityInterface $session_entity) : bool {
    return \Drupal::entityQuery('la_pills_questionnaire_entity')
      ->condition('session_id', $session_entity->id())
      ->count()
      ->execute() > 0;
  }

  /**
   * {@inheritdoc}
   */
  public function makeQuestionActive(LaPillsQuestionEntityInterface $question, AccountInterface $account) : void {
    if ($question->isNew()) {
      throw new \Exception('Only saved questions are allowed to be marked active or inactive!');
    }

    // TODO Need to make sure to check if it already is active for this user account
    $this->database->insert('user_active_question')
      ->fields([
        'user_id' => $account->id(),
        'question_id' => $question->id(),
        'created' => REQUEST_TIME,
      ])
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function makeQuestionInactive(LaPillsQuestionEntityInterface $question, AccountInterface $account) : void {
    if ($question->isNew()) {
      throw new \Exception('Only saved questions are allowed to be marked active or inactive!');
    }

    // TODO See if we need to check if question is active for this user account
    $this->database->delete('user_active_question')
      ->condition('user_id', $account->id())
      ->condition('question_id', $question->id())
      ->execute();
  }

}
