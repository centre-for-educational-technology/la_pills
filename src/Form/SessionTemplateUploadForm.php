<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\la_pills\SessionTemplateManagerInterface;
use Drupal\Core\Messenger\MessengerInterface;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Class SessionTemplateUploadForm.
 */
class SessionTemplateUploadForm extends FormBase {

  /**
   * Drupal\Core\Entity\EntityTypeManagerInterface definition.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * Drupal\la_pills\SessionTemplateManagerInterface definition.
   *
   * @var \Drupal\la_pills\SessionTemplateManagerInterface
   */
  protected $sessionTemplateManager;

  /**
   * Drupal\Core\Messenger\MessengerInterface definition.
   *
   * @var Drupal\Core\Messenger\MessengerInterface
   */
  protected $messenger;
  /**
   * Constructs a new SessionTemplateUploadForm object.
   */
  public function __construct(
    EntityTypeManagerInterface $entity_type_manager,
    SessionTemplateManagerInterface $session_template_manager,
    MessengerInterface $messenger
  ) {
    $this->entityTypeManager = $entity_type_manager;
    $this->sessionTemplateManager = $session_template_manager;
    $this->messenger = $messenger;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('la_pills.session_template_manager'),
      $container->get('messenger')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'session_template_upload';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['#attributes']['enctype'] = 'multipart/form-data';

    $form['session_template_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Session template file'),
      '#description' => $this->t('Session template formatted as .yaml/.yml file'),
      '#weight' => '0',
      '#upload_validators' => [
        'file_validate_extensions' => [
          'yaml yml',
        ],
      ],
      '#upload_location' => 'public://la_pills_template/',
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Upload'),
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
    $file = $this->entityTypeManager->getStorage('file')->load($form_state->getValue('session_template_file')[0]);

    if ($file) {
      $data = file_get_contents($file->getFileUri());

      try {
        $parsed = Yaml::parse($data);
      } catch(ParseException $e) {
        $this->messenger->addMessage($this->t('<strong>YAML file parse error!</strong>'), 'error');
        $this->messenger->addMessage($e->getMessage(), 'error');
        $file->delete();
        return;
      }

      if ($parsed && is_array($parsed)) {
        $errors = $this->sessionTemplateManager->validateTemplate($parsed);

        if (empty($errors)) {
          $this->sessionTemplateManager->addTemplate($parsed);
          $this->messenger->addMessage($this->t('Session template successfully imported.'));
        } else {
          foreach($errors as $message) {
            $this->messenger->addMessage($message, 'error');
          }
        }
      } else {
        $this->messenger->addMessage($this->t('Uploaded template file could not be parsed!'), 'error');
      }
      $file->delete();
    } else {
      $this->messenger->addMessage($this->t('No uploaded template file could be found!'), 'error');
    }
  }

}
