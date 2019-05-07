<?php

/**
 * @file
 * Contains session_entity.page.inc.
 *
 * Page callback for LA Pills Session entities.
 */

use Drupal\Core\Render\Element;
use Drupal\la_pills\RenderableHelper;
use Drupal\Core\Link;

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

  if ($session_entity->access('update')) {
    $variables['actions']['download_answers'] = RenderableHelper::downloadAnswersLink($session_entity)->toRenderable();

    if ($session_entity->isActive()) {
      $variables['actions']['close'] = Link::createFromRoute(
        t('Close session'),
        'session_entity.close',
        ['session_entity' => $session_entity->id()],
        [
          'attributes' => [
            'class' => ['button', 'close-session-button', 'btn', 'btn-warning', 'use-ajax'],
          ],
        ]
      )->toRenderable();
    }

    $variables['code'] = $session_entity->getCode();
  }

  $session_url = $session_entity->toUrl('canonical', ['absolute' => TRUE,])->toString();

  $replacements = [
    '{{website}}' => $session_entity->toLink($session_url, 'canonical', [
      'absolute' => TRUE,
      'attributes' => [
        'target' => '_blank',
      ],
    ])->toString(),
    '{{dashboard}}' => $session_entity->toLink(t('dashboard'), 'dashboard', [
      'absolute' => TRUE,
    ])->toString(),
  ];

  $session_template_data = $session_entity->getSessionTemplateData();
  if ($session_template_data && $session_template_data['questionnaires']) {
    foreach ($session_template_data['questionnaires'] as $questionnaire) {
      $replacements['{{' . $questionnaire['id'] . '}}'] = Link::createFromRoute($questionnaire['title'] . ' ' . t('form'), 'entity.session_entity.questionnaire', [
        'session_entity' => $session_entity->id(),
        'questionnaire_uuid' => $questionnaire['uuid'],
      ], [
        'absolute' => TRUE,
        'attributes' => [
          'data-questionnaire-uuid' => $questionnaire['uuid'],
        ],
      ])->toString();
    }
  }

  $variables['content']['session_template'] = [
    '#theme' => 'session_template',
    '#template' => $session_template_data,
    '#replacements' => $replacements,
  ];

  $variables['#attached']['library'][] = 'la_pills/session_entity';
  $variables['#attached']['drupalSettings']['laPillsSessionEntity'] = [
    'id' => $session_entity->id(),
    'canUpdate' => $session_entity->access('update'),
  ];
}
