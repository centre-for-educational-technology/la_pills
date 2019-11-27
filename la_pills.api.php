<?php

/**
 * @file
 * Documentation for LaPills module APIs.
 */

use Drupal\la_pills\Entity\SessionEntity;

/**
 * Provide additional rendered content for the Session Entity main view.
 * The array is the main content part of the view. Use #weight to set position.
 *
 * @param  array         $content
 *   Content array
 * @param  Drupal\la_pills\Entity\SessionEntity $session_entity
 *   Session Entity instance
 */
function hook_la_pills_session_entity_view_alter(array &$content, SessionEntity $session_entity) {
  $additional_content['strong'] = [
    '#type' => 'markup',
    '#markup' => '<strong>' . $session_entity->getName() . '</strong>',
  ];
}

/**
 * Allow template data to be altered.
 * NB! This would only be called in the contextof download results for Session
 * Entity questionnaires.
 *
 * @param  array         $template
 *   An array with template data structure
 * @param  SessionEntity $session_entity
 *   Session Entity instance
 */
function hook_la_pills_session_template_data_alter(array &$template, SessionEntity $session_entity) {
  $template['title'] = 'New template title';
}
