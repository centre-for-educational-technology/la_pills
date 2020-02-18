<?php

/**
 * @file
 * Contains la_pills_user_package.page.inc.
 *
 * Page callback for User package entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for User package templates.
 *
 * Default template: la_pills_user_package.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_la_pills_user_package(array &$variables) {
  // Fetch LaPillsUserPackageEntity Entity Object.
  $la_pills_user_package = $variables['elements']['#la_pills_user_package'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }
}
