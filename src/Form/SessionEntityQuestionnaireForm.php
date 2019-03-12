<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\la_pills\FetchClass\SessionTemplate;

/**
 * Class SessionEntityQUestionnaireForm.
 */
class SessionEntityQuestionnaireForm extends EntityForm {
  protected $questionnaire;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_entity_questionnaire_form';
  }

  /**
   * Determines if current user is allowed to answer the questionnaire
   *
   * @return boolean
   */
  private function canAnswer() {
    if(!($this->entity->isPublished() && $this->entity->isActive())) {
      return FALSE;
    }

    if (!$this->entity->getAllowAnonymousResponses() && \Drupal::currentUser()->isAnonymous()) {
      return FALSE;
    }

    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = \Drupal::routeMatch();
    $questionnaire_uuid = $route_match->getParameter('questionnaire_uuid');

    $session_template_data = $this->entity->getSessionTemplateData();

    if(!isset($session_template_data['questionnaires'][$questionnaire_uuid])) {
      \Drupal::messenger()->addMessage($this->t('No such questionnaire found.'), 'warning');
      return [];
    }

    $this->questionnaire = $session_template_data['questionnaires'][$questionnaire_uuid];

    $form['questionnaire'] = [
      '#markup' => '<h2>' . $this->questionnaire['title'] . '</h2>',
    ];

    $form['questions'] = [
      '#attached' => [
        'library' => [
          'la_pills/questionnaire'
        ],
      ],
    ];

    foreach ($this->questionnaire['questions'] as $question) {
      $structure = [
        '#title' => $question['title'],
        '#required' => ($question['required'] === 'Yes') ? TRUE : FALSE
      ];

      switch(SessionTemplate::processQuestionType($question['type'])) {
        case 'short-text':
        $structure['#type'] = 'textfield';
        break;
        case 'long-text':
        $structure['#type'] = 'textarea';
        $structure['#rows'] = 5;
        break;
        case 'scale':
        $range = range($question['min'], $question['max']);
        $structure['#type'] = 'radios';
        $structure['#options'] = array_combine($range, $range);
        $structure['#attributes']['class'] = ['scale'];
        break;
        case 'multi-choice':
        $structure['#type'] = 'radios';
        $structure['#options'] = array_combine($question['options'], $question['options']);
        break;
        case 'checkboxes':
        $structure['#type'] = 'checkboxes';
        $structure['#options'] = array_combine($question['options'], $question['options']);
        break;
      }

      $form['questions'][$question['uuid']] = $structure;
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    if (!($this->entity->isPublished() && $this->entity->isActive())) {
      \Drupal::messenger()->addMessage($this->t('Current session is either unpublished or set to be inactive. Questionnaires can not be answerd!'), 'warning');
    }

    if (!$this->canAnswer()) {
      $form['submit']['#attributes']['disabled'] = 'disabled';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->canAnswer()) {
      return FALSE;
    }

    if (!isset($this->questionnaire)) {
      return FALSE;
    }

    // Make sure the session exists in case of an anonymous user
    if (\Drupal::currentUser()->isAnonymous() && !\Drupal::request()->getSession()) {
      \Drupal::service('session_manager')->start();
    }

    $connection = \Drupal::database();

    $values = $form_state->getValues();
    $records = [];

    foreach ($this->questionnaire['questions'] as $question) {
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
      }

      foreach($answers as $answer) {
        $records[] = [
          'session_entity_uuid' => $this->entity->uuid(),
          'questionnaire_uuid' => $this->questionnaire['uuid'],
          'question_uuid' => $question['uuid'],
          'session_id' => \Drupal::request()->getSession()->getId(),
          'form_build_id' => $values['form_build_id'],
          'user_id' => (\Drupal::currentUser()->isAnonymous()) ? NULL : \Drupal::currentUser()->id(),
          'answer' => $answer,
          'created' => REQUEST_TIME,
        ];
      }
    }

    $query = $connection->insert('session_questionnaire_answer')->fields(['session_entity_uuid', 'questionnaire_uuid', 'question_uuid', 'session_id', 'form_build_id', 'user_id', 'answer', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }
    $query->execute();

    \Drupal::messenger()->addMessage($this->t('Thanks you for responding to this questionnaire. Please proceed to the <a href="@link">session page</a>.', ['@link' => $this->entity->toUrl('canonical', ['absolute' => TRUE,])->toString()]));
  }

}
