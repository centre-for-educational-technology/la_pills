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

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];
    $form['questionnaire'] = [
      '#weight' => '-1',
      '#attached' => [
        'library' => [
          'la_pills/questionnaire'
        ],
      ],
      '#theme' => 'session_entity_questionnaire',
      '#session_entity' => $this->entity,
      '#questionnaire' => $this->questionnaire,
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
