<?php

/**
 * @file
 * Documentation for LaPills module APIs.
 */

use Drupal\la_pills\Entity\SessionEntity;

/**
 * Provide additional rendered content for the Session Entity main view. The
 * array is initially empty and would be added to the bottom of the view.
 *
 * @param  array         $additional_content
 *   Additional content
 * @param  Drupal\la_pills\Entity\SessionEntity $session_entity
 *   Session Entity instance
 */
function hook_la_pills_session_entity_view(array &$additional_content, SessionEntity $session_entity) {
  $additional_content['strong'] = [
    '#type' => 'markup',
    '#markup' => '<strong>' . $session_entity->getName() . '</strong>',
  ];
}
