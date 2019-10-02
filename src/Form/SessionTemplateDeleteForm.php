<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\la_pills\SessionTemplateManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Database\Connection;
use Drupal\la_pills\FetchClass\SessionTemplate;
use Drupal\la_pills\Entity\SessionEntity;
use Drupal\Core\Url;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Class SessionTemplateDeleteForm.
 */
class SessionTemplateDeleteForm extends FormBase {

  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;
  /**
   * Drupal\la_pills\SessionTemplateManagerInterface definition.
   *
   * @var \Drupal\la_pills\SessionTemplateManagerInterface
   */
  protected $sessionTemplateManager;
  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * Constructs a new SessionTemplateDeleteForm object.
   */
  public function __construct(
    Connection $connection,
    SessionTemplateManagerInterface $session_template_manager,
    MessengerInterface $messenger
  ) {
    $this->connection = $connection;
    $this->sessionTemplateManager = $session_template_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('database'),
      $container->get('la_pills.session_template_manager'),
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_template_delete';
  }

  /**
   * Returns count of eixisting Session Templates
   * @param  \Drupal\la_pills\FetchClass\SessionTemplate $template
   *   Session Template object
   *
   * @return int
   *   Count of existing templates
   */
  private function getTemplateSessionsCount(SessionTemplate $template) {
    return (int)$this->connection->select('session_entity', 'se')
    ->condition('se.template', $template->uuid, '=')
    ->countQuery()
    ->execute()
    ->fetchField();
  }

  /**
   * Retruns Sessin identifiers based on the Template
   * @param  \Drupal\la_pills\FetchClass\SessionTemplate $template
   *   Session Template object
   *
   * @return array
   *   Array with Session identifiers
   */
  private function getTemplateSessionIds(SessionTemplate $template) {
    return $this->connection->select('session_entity', 'se')
    ->fields('se', ['id',])
    ->condition('se.template', $template->uuid, '=')
    ->execute()
    ->fetchCol();
  }

  /**
   * Remove Template from the system. Also remove any Sessions based on this
   * Tempate and their results.
   * @param  \Drupal\la_pills\FetchClass\SessionTemplate $template
   *   Session template object
   *
   * @return int
   *   Number of records deleted
   */
  private function deleteTemplate(SessionTemplate $template) {
    $ids = $this->getTemplateSessionIds($template);

    if ($ids) {
      foreach ($ids as $id) {
        $entity = SessionEntity::load($id);
        $entity->delete();
      }
    }

    return $this->connection->delete('session_template')
    ->condition('uuid', $template->uuid, '=')
    ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $request = $this->getRequest();

    $session_template = $this->sessionTemplateManager->getTemplate($request->attributes->get('session_template'));

    if (!$session_template) {
      throw new NotFoundHttpException();
    }

    $form['#title'] = $this->t('Delete Session Template: @title', [
      '@title' => $session_template->getTitle(),
    ]);

    $form['content'] = [
      '#type' => 'container',
    ];

    $count = $this->getTemplateSessionsCount($session_template);

    if ($count > 0) {
      $explanation = $this->t('This template is used for @count sessions. All sessions and their results will be removed along with the template. This operation can not be undone.', [
        '@count' => $count,
      ]);
    } else {
      $explanation = $this->t('This operation can not be undone.');
    }

    $form['content']['explanation'] = [
      '#type' => 'html_tag',
      '#tag' => 'strong',
      '#value' => $explanation,
    ];

    $form['actions'] = [
      '#type' => 'container',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Delete'),
      '#attached' => [
        'library' => [
          'core/drupal.form',
        ],
      ],
    ];
    $form['actions']['cancel'] = [
      '#type' => 'link',
      '#title' => $this->t('Cancel'),
      '#attributes' => [
        'class' => [
          'button',
        ],
      ],
      '#url' => Url::fromRoute('session_templates.manage', [], [
        'absolute' => TRUE,
      ]),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $request = $this->getRequest();
    $session_template = $this->sessionTemplateManager->getTemplate($request->attributes->get('session_template'));
    $this->deleteTemplate($session_template);
    $this->messenger->addMessage($this->t('Successfully removed Session Template: @title', [
      '@title' => $session_template->getTitle(),
    ]));
    $form_state->setRedirect('session_templates.manage');
  }

}
