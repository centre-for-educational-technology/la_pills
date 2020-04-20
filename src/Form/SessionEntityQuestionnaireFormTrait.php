<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Url;
use Drupal\la_pills\FetchClass\SessionTemplate;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class SessionEn.
 */
trait SessionEntityQuestionnaireFormTrait {

  /**
   * Questionnaire
   *
   * @var array
   */
  protected $questionnaire;

  /**
   * Returns ananymous user name key stored within current session.
   *
   * @return string
   *   Name key.
   */
  public static function getNameKey() {
    return LA_PILLS_NAME_KEY;
  }

  /**
   * Determines if current user is allowed to answer the questionnaire
   *
   * @return boolean
   */
  public function canAnswer() {
    if(!($this->entity->isPublished() && $this->entity->isActive())) {
      return FALSE;
    }

    if (!$this->entity->getAllowAnonymousResponses() && \Drupal::currentUser()->isAnonymous()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * Determines if the name field should be shown. The field is only shown once and the data is stored for later use.
   *
   * @return boolean
   *   TRUE if anonymous and name is required and name is not set, FALSE otherwise
   */
  public function showNameField() {
    if (\Drupal::currentUser()->isAnonymous() && $this->entity->getRequireName() && !\Drupal::request()->getSession()->has(self::getNameKey())) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Returns a name value provided by an anonymous user if one is set.
   *
   * @return mixed
   *   A name if it is set, NULL otherwise
   */
  public function getProvidedName() {
    if (\Drupal::currentUser()->isAnonymous() && \Drupal::request()->getSession()->has(self::getNameKey())) {
      return \Drupal::request()->getSession()->get(self::getNameKey());
    }

    return NULL;
  }

  /**
   * Adds name filed to a form in case Session Entity has name required. Only
   * affects anonymous users.
   *
   * @param array $form
   *   Form renderable structure.
   */
  public function addNameFieldToForm(array &$form) {
    if ($this->showNameField()) {
      $form['name'] = [
        '#title' => $this->t('Name'),
        '#description' => $this->t('Anonymous user is required to provide a name!'),
        '#placeholder' => $this->t('Your name'),
        '#required' => TRUE,
        '#type' => 'textfield',
        '#wrapper_attributes' => [
          'class' => ['well'],
        ],
      ];
    } else if (\Drupal::currentUser()->isAnonymous() && $this->entity->getRequireName()) {
      $form['user_data'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['la-pills-user-data', 'alert', 'alert-info'],
          'id' => 'la-pills-user-name',
        ],
      ];
      $form['user_data']['label'] = [
        '#markup' => '<strong><span class="icon glyphicon glyphicon-user" aria-hidden="true"></span> ' . $this->t('Name provided by you:') . '</strong>',
      ];
      $form['user_data']['name'] = [
        '#markup' => ' <span>' . $this->getProvidedName() . '</span>',
      ];
      $form['user_data']['reset_name'] = [
        '#type' => 'button',
        '#name' => 'reset_name',
        '#value' => $this->t('Not your name?'),
        '#attributes' => [
          'class' => ['la-pills-name-reset', 'btn', 'btn-xs', 'btn-warning'],
          'title' => $this->t('Click here to reset the current name! You can enter a new one once that is done.'),
          'data-toggle' => 'tooltip',
        ],
        '#ajax' => [
          'callback' => [$this, 'resetNameCallback'],
          'effect' => 'fade',
        ],
        '#limit_validation_errors' => [],
      ];
    }
  }

  /**
   * Starts a session if one does not yet exist in for anonymous users.
   *
   * @return void
   */
  public function forceStartSession() {
    if (\Drupal::currentUser()->isAnonymous() && !\Drupal::request()->getSession()) {
      \Drupal::service('session_manager')->start();
    }
  }

  /**
   * Creates a question renderable structure.
   *
   * @param  array  $question
   *   Question data, should have the smae strcuture as the one in templates.
   *
   * @return array
   *   Renderable structure for a question.
   */
  public static function createQuestionRenderable(array $question) {
    $uuid = $question['uuid'];
    $type = SessionTemplate::processQuestionType($question['type']);

    $structure = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['questionnaire-question', 'questionnaire-question-' . $type,],
      ],
    ];
    $structure['question'] = [
      '#type' => 'html_tag',
      '#tag' => 'label',
      '#value' => $question['title'],
      '#attributes' => [
        'class' => ['question'],
      ],
    ];
    if (isset($question['description']) && $question['description']) {
      $structure['description'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#plain_text' => $question['description'],
        '#attributes' => [
          'class' => ['well', 'question-description',],
        ],
      ];
    }
    $structure[$uuid] = [
      '#required' => ($question['required'] === 'Yes') ? TRUE : FALSE
    ];

    switch($type) {
      case 'short-text':
      $structure[$uuid]['#type'] = 'textfield';
      break;
      case 'long-text':
      $structure[$uuid]['#type'] = 'textarea';
      $structure[$uuid]['#rows'] = 5;
      break;
      case 'scale':
      $range = range($question['min'], $question['max']);
      $structure[$uuid]['#type'] = 'radios';
      $structure[$uuid]['#options'] = array_combine($range, $range);
      $structure[$uuid]['#attributes']['class'] = ['scale'];
      break;
      case 'multi-choice':
      $structure[$uuid]['#type'] = 'radios';
      $structure[$uuid]['#options'] = array_combine($question['options'], $question['options']);
      break;
      case 'checkboxes':
      $structure[$uuid]['#type'] = 'checkboxes';
      $structure[$uuid]['#options'] = array_combine($question['options'], $question['options']);
      break;
    }

    return $structure;
  }

  /**
   * Name reset ajax callback. Resets the name if present.
   */
  public function resetNameCallback() {
    $response = new AjaxResponse();

    if (\Drupal::currentUser()->isAnonymous() && \Drupal::request()->getSession()->has(self::getNameKey())) {
      $currentURL = Url::fromRoute('<current>');

      \Drupal::request()->getSession()->remove(self::getNameKey());

      $response->addCommand(new RemoveCommand('#la-pills-user-name'));
      $response->addCommand(new RedirectCommand($currentURL->toString()));
    }

    return $response;
  }

  /**
   * Returns questions of a current questionnaire.
   *
   * @return array
   *   Questions of current questionnaire.
   */
  abstract public function getQuestions();

  /**
   * Returns questionnaire UUID identifier.
   *
   * @return string
   *   Questionnaire UUID.
   */
  abstract public function getQuestionnaireUuid();

  /**
   * Stores name if one has been provided.
   *
   * @param  Drupal\Core\Form\FormStateInterface $form_state
   *   FromState object.
   *
   * @return void
   */
  public function storeNameValue(FormStateInterface $form_state) {
    if ($form_state->hasValue('name')) {
      \Drupal::request()->getSession()->set(self::getNameKey(), $form_state->getValue('name'));
    }
  }

  /**
   * Store questionnaire answers in the database.
   *
   * @param  Drupal\Core\Form\FormStateInterface $form_state
   *   FormState object.
   *
   * @return void
   */
  public function storeQuestionnaireAnswers(FormStateInterface $form_state) {
    $connection = \Drupal::database();

    $values = $form_state->getValues();
    $records = [];

    foreach ($this->getQuestions() as $question) {
      $answers = $values[$question['uuid']];

      if (is_array($answers)) {
        // Remove any unchecked value for 'checkboxes' case
        foreach($answers as $key => $value) {
          if ($key !== $value && $value === 0) {
            unset($answers[$key]);
          }
        }
      }

      if (!is_array($answers)) {
        $answers = [$answers];
      } else if (is_array($answers) && empty($answers)) {
        // Add NULL value for 'checkboxes' case
        $answers[] = NULL;
      }

      foreach($answers as $answer) {
        $records[] = [
          'session_entity_uuid' => $this->entity->uuid(),
          'questionnaire_uuid' => $this->getQuestionnaireUuid(),
          'question_uuid' => $question['uuid'],
          'session_id' => \Drupal::request()->getSession()->getId(),
          'form_build_id' => $values['form_build_id'],
          'user_id' => (\Drupal::currentUser()->isAnonymous()) ? NULL : \Drupal::currentUser()->id(),
          'name' => (\Drupal::currentUser()->isAnonymous() && $this->entity->getRequireName()) ? \Drupal::request()->getSession()->get(self::getNameKey()) : NULL,
          'answer' => $answer,
          'created' => REQUEST_TIME,
        ];
      }
    }

    $query = $connection->insert('session_questionnaire_answer')->fields(['session_entity_uuid', 'questionnaire_uuid', 'question_uuid', 'session_id', 'form_build_id', 'user_id', 'name', 'answer', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }
    $query->execute();
  }
}
