<?php

/**
 * @file
 * Contains la_pills_timer_entity.page.inc.
 *
 * Page callback for LA Pills Timer entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for LA Pills Timer templates.
 *
 * Default template: la_pills_timer_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_la_pills_timer_entity(array &$variables) {
  // Fetch LaPillsTimerEntity Entity Object.
  $la_pills_timer_entity = $variables['elements']['#la_pills_session_timer_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
