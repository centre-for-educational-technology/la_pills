<?php

/**
 * @file
 * Contains la_pills_question_entity.page.inc.
 *
 * Page callback for LaPills Question Entity entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for LaPills Question Entity templates.
 *
 * Default template: la_pills_question_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_la_pills_question_entity(array &$variables) {
  // Fetch LaPillsQuestionEntity Entity Object.
  $la_pills_question_entity = $variables['elements']['#la_pills_question_entity'];

  // Helpful $content variable for templates.
  /*foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }*/

  $variables['content']['preview'] = \Drupal::formBuilder()->getForm('Drupal\la_pills_quick_feedback\Form\QuestionPreviewForm', $la_pills_question_entity);
}
