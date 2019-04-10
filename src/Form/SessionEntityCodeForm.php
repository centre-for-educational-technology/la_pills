<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Messenger\Messenger;

/**
 * Class SessionEntityCodeForm.
 */
class SessionEntityCodeForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface instance.
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\Core\Messenger\Messenger instance.
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Constructs a new SessionEntityCodeForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    Messenger $messenger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('messenger')
    );
  }


  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_entity_code';
  }

  /**
   * Find Session Entity by code.
   *
   * @param  string $code
   *   Code
   *
   * @return mixed
   *   Entity ID or NULL if not found
   */
  private function findSessionEntityByCode(string $code) {
    $query = $this->entityTypeManager->getStorage('session_entity')->getQuery();
    $query->condition('code', $code);
    $query->range(0, 1);
    $result = $query->execute();

    return $result && sizeof($result) > 0 ? (int)array_values($result)[0] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['code'] = [
      '#type' => 'container',
      '#attached' => [
        'library' => [
          'la_pills/session_entity_code'
        ],
      ],
      '#attributes' => [
        'class' => ['session-entity-code'],
      ],
    ];
    $form['code']['code'] = [
      '#type' => 'textfield',
      '#title' => 'Session PIN code',
      '#title_display' => 'invisible',
      '#placeholder' => $this->t('Session PIN code'),
      '#required' => TRUE,
      '#size' => 15,
      '#maxlength' => 25,
    ];
    $form['code']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Enter'),
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
    $values = $form_state->getValues();

    if (isset($values['code'])) {
      $id = $this->findSessionEntityByCode(trim($values['code']));

      if ($id) {
        $form_state->setRedirect('entity.session_entity.canonical', ['session_entity' => $id]);
      } else {
        $this->messenger->addMessage($this->t('Could not find a session for code: <strong>@code</strong>', ['@code' => $values['code']]), 'warning');
      }
    }
  }

}
