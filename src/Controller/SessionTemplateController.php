<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Drupal\la_pills\SessionTemplateManager;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\Url;
use Drupal\Core\Link;

/**
 * Class SessionTemplateController.
 */
class SessionTemplateController extends ControllerBase {

  /**
   * Session Template Manager
   *
   * @var Drupal\la_pills\SessionTemplateManager
   */
  protected $sessionTemplateManager;

  /**
   * Controller constructor
   *
   * @param Drupal\la_pills\SessionTemplateManager $session_template_manager
   *   Sessin Template Manager
   */
  public function __construct(SessionTemplateManager $session_template_manager) {
    $this->sessionTemplateManager = $session_template_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $session_template_manager = $container->get('la_pills.session_template_manager');

    return new static($session_template_manager);
  }

  /**
   * Returns Session Template preview page title
   *
   * @param  Symfony\Component\HttpFoundation\Request $request
   *   Request object
   *
   * @return string
   *  Page title
   */
  public function previewTitle(Request $request) {
    $session_template = $this->sessionTemplateManager->getTemplate($request->attributes->get('session_template'));
    $session_template_data = $session_template->getData();

    return $this->t('Session Template Preview: @title', [
      '@title' => $session_template_data['context']['title'],
    ]);
  }

  /**
   * Returns Session Template preview page structure
   *
   * @param  Symfony\Component\HttpFoundation\Request $request
   *   Request object
   *
   * @return array
   *   Content structure
   */
  public function preview(Request $request) {
    $session_template = $this->sessionTemplateManager->getTemplate($request->attributes->get('session_template'));

    if (!$session_template) {
      throw new NotFoundHttpException();
    }

    $session_template_data = $session_template->getData();

    $current_url = Url::fromRoute('session_template.preview', [
      'session_template' => $session_template->uuid,
    ], [
      'absolute' => TRUE,
    ]);

    $replacements = [
      '{{website}}' => Link::fromTextAndUrl($current_url->toString(), $current_url)->toString(),
      '{{dashboard}}' => Link::fromTextAndUrl($this->t('dashboard'), $current_url)->toString(),
    ];

    if ($session_template_data['questionnaires']) {
      foreach ($session_template_data['questionnaires'] as $questionnaire) {
        $replacements['{{' . $questionnaire['id'] . '}}'] = Link::fromTextAndUrl($questionnaire['title'] . ' ' . $this->t('form'), $current_url)->toString();
      }
    }

    $response['template'] = [
      '#theme' => 'session_template',
      '#template' => $session_template_data,
      '#replacements' => $replacements,
    ];

    return $response;
  }

}
