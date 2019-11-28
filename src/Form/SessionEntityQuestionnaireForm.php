<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\la_pills\Form\SessionEntityQuestionnaireFormTrait;

/**
 * Class SessionEntityQUestionnaireForm.
 */
class SessionEntityQuestionnaireForm extends EntityForm {

  use SessionEntityQuestionnaireFormTrait;

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_entity_questionnaire_form';
  }

  /**
   * Returns questionnaire object for current form, if one exists.
   *
   * @return mixed
   *   Array with questionnaire data or NULL.
   */
  public function getQuestionnaire() {
    $route_match = \Drupal::routeMatch();

    $questionnaire_uuid = $route_match->getParameter('questionnaire_uuid');

    $session_template_data = $this->entity->getSessionTemplateData();

    return $session_template_data['questionnaires'][$questionnaire_uuid] ?? NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestions() {
    return $this->questionnaire['questions'];
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestionnaireUuid() {
    return $this->questionnaire['uuid'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $questionnaire = $this->getQuestionnaire();

    if(!$questionnaire) {
      \Drupal::messenger()->addMessage($this->t('No such questionnaire found.'), 'warning');
      return [];
    }

    $this->questionnaire = $questionnaire;

    $this->addNameFieldToForm($form);

    $form['questionnaire']['title'] = [
      '#markup' => '<h2>' . $this->questionnaire['title'] . '</h2>',
    ];

    if (isset($this->questionnaire['description']) && $this->questionnaire['description']) {
      $form['questionnaire']['description'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['well', 'questionnaire-description'],
        ],
        '#plain_text' => $this->questionnaire['description'],
      ];
    }

    $form['questions'] = [
      '#attached' => [
        'library' => [
          'la_pills/questionnaire'
        ],
      ],
    ];

    foreach ($this->getQuestions() as $question) {
      $form['questions'][$question['uuid']] = $this->createQuestionRenderable($question);
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
    $this->forceStartSession();

    $this->storeNameValue($form_state);

    $this->storeQuestionnaireAnswers($form_state);

    \Drupal::messenger()->addMessage($this->t('Thanks you for responding to this questionnaire. Please proceed to the <a href="@link">session page</a>.', ['@link' => $this->entity->toUrl('canonical', ['absolute' => TRUE,])->toString()]));
  }

}
