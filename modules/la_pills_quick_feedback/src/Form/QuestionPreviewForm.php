<?php

namespace Drupal\la_pills_quick_feedback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;
use Drupal\la_pills\Form\SessionEntityQuestionnaireFormTrait;

/**
 * Class QuestionPreviewForm.
 */
class QuestionPreviewForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'question_preview_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, LaPillsQuestionEntityInterface $question = NULL) {
    $form['preview'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'la_pills_quick_feedback/question_preview',
          'la_pills/fontawesome',
          'la_pills/questionnaire',
        ],
      ],
    ];

    $tmp = $question->getQuesionDataForQuestionnaire();
    $tmp['required'] = 'No';
    $tmp['title'] = '<i class="' . $tmp['icon'] . '"></i> ' . $tmp['title'];

    $form['preview']['question'] = SessionEntityQuestionnaireFormTrait::createQuestionRenderable($tmp);

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
