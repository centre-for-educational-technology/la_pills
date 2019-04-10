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
   * Determines if the name field should be shown. The field is only shown once and the data is stored for later use.
   *
   * @return boolean
   *   TRUE if anonymous and name is required and name is not set, FALSE otherwise
   */
  private function showNameField() {
    if (\Drupal::currentUser()->isAnonymous() && $this->entity->getRequireName() && !\Drupal::request()->getSession()->has('la_pills_name')) {
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
  private function getProvidedName() {
    if (\Drupal::currentUser()->isAnonymous() && \Drupal::request()->getSession()->has('la_pills_name')) {
      return \Drupal::request()->getSession()->get('la_pills_name');
    }

    return NULL;
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
        ],
      ];
      $form['user_data']['label'] = [
        '#markup' => '<strong><span class="icon glyphicon glyphicon-user" aria-hidden="true"></span> ' . $this->t('Name provided by you:') . '</strong>',
      ];
      $form['user_data']['name'] = [
        '#markup' => ' <span>' . $this->getProvidedName() . '</span>',
      ];
      // TODO Need to provide a possibility to change the name by resetting the session
      // Using \Drupal::request()->getSession()->clear(); might be a decent enough solution
    }

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

    if (isset($values['name'])) {
      \Drupal::request()->getSession()->set('la_pills_name', $values['name']);
    }

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
          'name' => (\Drupal::currentUser()->isAnonymous() && $this->entity->getRequireName()) ? \Drupal::request()->getSession()->get('la_pills_name') : NULL,
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

    \Drupal::messenger()->addMessage($this->t('Thanks you for responding to this questionnaire. Please proceed to the <a href="@link">session page</a>.', ['@link' => $this->entity->toUrl('canonical', ['absolute' => TRUE,])->toString()]));
  }

}
