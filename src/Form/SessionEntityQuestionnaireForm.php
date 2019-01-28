<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

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
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $route_match = \Drupal::routeMatch();

    $this->questionnaire = $this->entity->getSessionTemplateData()['questionnaires'][$route_match->getParameter('questionnaire_uuid')];

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

      switch($question['type']) {
        case 'Short text':
        $structure['#type'] = 'textfield';
        break;
        case 'Long text':
        $structure['#type'] = 'textarea';
        $structure['#rows'] = 5;
        break;
        case 'Scale':
        $range = range($question['min'], $question['max']);
        $structure['#type'] = 'radios';
        $structure['#options'] = array_combine($range, $range);
        $structure['#attributes']['class'] = ['scale'];
        break;
        case 'Multi-choice':
        $structure['#type'] = 'radios';
        $structure['#options'] = array_combine($question['options'], $question['options']);
        break;
        case 'Checkboxes':
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
      drupal_set_message($this->t('Current session is either unpublished or set to be inactive. Questionnaires can not be answerd!'), 'warning');
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
    // XXX Need a more meaningful way for dealing with that
    // Need to make sure that session does persist and is not just constantly regenerated
    if (\Drupal::currentUser()->isAnonymous() && !\Drupal::request()->getSession()) {
      \Drupal::service('session_manager')->start();
    }

    $connection = \Drupal::database();
    $values = $form_state->getValues();
    $session_id = \Drupal::request()->getSession()->getId();
    $records = [];

    foreach ($this->questionnaire['questions'] as $question) {
      $answers = $values[$question['uuid']];

      if (is_array($answers)) {
        foreach($answers as $key => $value) {
          if ($key !== $value && $value === 0) {
            unset($answers[$key]);
          }
        }
      }

      if (!is_array($answers)) {
        $answers = [$answers];
      }

      // TODO See if we need to store empty answers for questions that are not required

      foreach($answers as $answer) {
        $records[] = [
          'session_entity_uuid' => $this->entity->uuid(),
          'questionnaire_uuid' => $this->questionnaire['uuid'],
          'question_uuid' => $question['uuid'],
          'session_id' => $session_id, // XXX See if this identifier will always fit into the storage
          //'UNIQUE_FORM_SUBMIT' => '', // XXX Need to make sure that we could track each unique form submit
          'answer' => $answer,
          'created' => REQUEST_TIME,
        ];
      }
    }

    $query = $connection->insert('session_questionnaire_answer')->fields(['session_entity_uuid', 'questionnaire_uuid', 'question_uuid', 'session_id', 'answer', 'created']);
    foreach ($records as $record) {
      $query->values($record);
    }
    $query->execute();

  }

}
