<?php

namespace Drupal\la_pills_quick_feedback\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\la_pills_quick_feedback\LaPillsQuickFeedbackManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\la_pills\Entity\SessionEntityInterface;
use Drupal\la_pills\Form\SessionEntityQuestionnaireFormTrait;

/**
 * Class SessionEntityQuickFeedbackForm.
 */
class SessionEntityQuickFeedbackForm extends FormBase {

  use SessionEntityQuestionnaireFormTrait;

  /**
   * Drupal\la_pills_quick_feedback\LaPillsQuickFeedbackManagerInterface definition.
   *
   * @var \Drupal\la_pills_quick_feedback\LaPillsQuickFeedbackManagerInterface
   */
  protected $manager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var \Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;

  /**
   * SessionEntity
   *
   * @var array
   */
  protected $entity;


  /**
   * Questionnaire
   *
   * @var \Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionnaireEntityInterface
   */
  protected $questionnaire;

  /**
   * Constructs a new SessionEntityQuickFeedbackForm object.
   */
  public function __construct(
    LaPillsQuickFeedbackManagerInterface $manager,
    MessengerInterface $messenger
  ) {
    $this->manager = $manager;
    $this->messenger = $messenger;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('la_pills_quick_feedback.manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_entity_quick_feedback_form';
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestions() {
    return $this->questionnaire->getQuestions();
  }

  /**
   * {@inheritdoc}
   */
  public function getQuestionnaireUuid() {
    return $this->questionnaire->uuid();
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, SessionEntityInterface $session_entity = NULL) {
    $this->entity = $session_entity;
    $this->questionnaire = $this->manager->getQuickFeedbackQuestionnaire($session_entity);

    if(!$this->questionnaire) {
      \Drupal::messenger()->addMessage($this->t('No quick feedback questionnaire found.'), 'warning');
      return [];
    }

    $this->addNameFieldToForm($form);

    $form['questions'] = [
      '#attached' => [
        'library' => [
          'la_pills_quick_feedback/fontawesome',
          'la_pills/questionnaire',
        ],
      ],
    ];

    foreach ($this->getQuestions() as $question) {
      $question['required'] = 'No';

      $form['questions'][$question['uuid']] = $this->createQuestionRenderable($question);
      $form['questions'][$question['uuid']]['#title'] = '<i class="' . $question['icon'] . '"></i> ' . $form['questions'][$question['uuid']]['#title'];

      if (!empty($question['description'])) {
        $form['questions'][$question['uuid'] . '-description'] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['well', 'questionnaire-description'],
          ],
          '#plain_text' => $question['description'],
        ];
      }
    }

    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    if (!($this->entity->isPublished() && $this->entity->isActive())) {
      \Drupal::messenger()->addMessage($this->t('Current session is either unpublished or set to be inactive. Quick feedback questionnaire can not be answerd!'), 'warning');
    }

    if (!$this->canAnswer()) {
      $form['submit']['#attributes']['disabled'] = 'disabled';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    foreach ($form_state->getValues() as $key => $value) {
      // @TODO: Validate fields.
    }
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    if (!$this->canAnswer()) {
      return FALSE;
    }

    if (!isset($this->questionnaire)) {
      return FALSE;
    }

    // Make sure the session exists in case of an anonymous user
    $this->forceStartSession();

    $this->storeNameValue($form_state);

    $this->storeQuestionnaireAnswers($form_state);

    \Drupal::messenger()->addMessage($this->t('Thanks you for responding to this quick feedback questionnaire. Please proceed to the <a href="@link">session page</a>.', ['@link' => $this->entity->toUrl('canonical', ['absolute' => TRUE,])->toString()]));
  }

}
