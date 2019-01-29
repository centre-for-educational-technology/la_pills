<?php

/**
 * @file
 * Contains session_entity.page.inc.
 *
 * Page callback for LA Pills Session entities.
 */

use Drupal\Core\Render\Element;

/**
 * Prepares variables for LA Pills Session templates.
 *
 * Default template: session_entity.html.twig.
 *
 * @param array $variables
 *   An associative array containing:
 *   - elements: An associative array containing the user information and any
 *   - attributes: HTML attributes for the containing element.
 */
function template_preprocess_session_entity(array &$variables) {
  // Fetch SessionEntity Entity Object.
  $session_entity = $variables['elements']['#session_entity'];

  // Helpful $content variable for templates.
  foreach (Element::children($variables['elements']) as $key) {
    $variables['content'][$key] = $variables['elements'][$key];
  }

  $websiteUrl = $session_entity->toUrl('canonical', ['absolute' => TRUE,])->toString();
  $dashboardUrl = $session_entity->toUrl('dashboard', ['absolute' => TRUE,])->toString();

  $variables['session_entity_template'] = $session_entity->getSessionTemplateData();
  $variables['session_entity_replacements'] = [
    '{{website}}' => '<a href="' . $websiteUrl. '" target="_blank">' . $websiteUrl. '</a>',
    '{{dashboard}}' => '<a href="' . $dashboardUrl . '" target="_blank">' . t('dashboard') . '</a>',
  ];

  if ($variables['session_entity_template'] && $variables['session_entity_template']['questionnaires']) {
    foreach ($variables['session_entity_template']['questionnaires'] as $questionnaire) {
      $variables['session_entity_replacements']['{{' . $questionnaire['id'] . '}}'] = '<a href="' . $websiteUrl . '/questionnaire/' . $questionnaire['uuid']  . '/" data-questionnaire-uuid="' . $questionnaire['uuid'] . '">' . $questionnaire['title'] . ' ' . t('form') . '</a>';
    }
  }

  $variables['#attached']['library'][] = 'la_pills/session_entity';
  $variables['#attached']['drupalSettings']['laPillsSessionEntity'] = [
    'id' => $session_entity->id(),
    'canUpdate' => $session_entity->access('update'),
  ];
}
