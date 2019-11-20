<?php

namespace Drupal\la_pills_quick_feedback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityInterface;

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
          'la_pills_quick_feedback/fontawesome',
          'la_pills/questionnaire',
        ],
      ],
    ];


    $structure = [
      '#title' => '<i class="' . $question->get('icon')->value . '"></i> ' . $question->get('prompt')->value,
      '#required' => FALSE,
    ];

    // TODO Consider making question renderable reusable
    switch($question->get('type')->value) {
      case 'short-text':
      $structure['#type'] = 'textfield';
      break;
      case 'long-text':
      $structure['#type'] = 'textarea';
      $structure['#rows'] = 5;
      break;
      case 'scale':
      $data = $question->get('data')->getValue();
      $range = range($data[0]['range']['min'], $data[0]['range']['max']);
      $structure['#type'] = 'radios';
      $structure['#options'] = array_combine($range, $range);
      $structure['#attributes']['class'] = ['scale'];
      break;
      case 'multi-choice':
      $data = $question->get('data')->getValue();
      $structure['#type'] = 'radios';
      $structure['#options'] = array_combine($data[0]['options'], $data[0]['options']);
      break;
      case 'checkboxes':
      $data = $question->get('data')->getValue();
      $structure['#type'] = 'checkboxes';
      $structure['#options'] = array_combine($data[0]['options'], $data[0]['options']);
      break;
    }

    $form['preview']['question'] = $structure;

    if (!empty($question->get('description')->value)) {
      $form['preview']['description'] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['well', 'questionnaire-description'],
        ],
        '#plain_text' => $question->get('description')->value,
      ];
    }

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
