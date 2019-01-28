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
    // Display result.
    foreach ($form_state->getValues() as $key => $value) {
      drupal_set_message($key . ': ' . $value);
    }

  }

}
