<?php

namespace Drupal\la_pills_analytics\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;

/**
 * Class ReportsFilterForm.
 */
class ReportsFilterForm extends FormBase {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $instance = parent::create($container);
    $instance->database = $container->get('database');
    return $instance;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reports_filter_form';
  }

  /**
   * Fetches and returns all collected activity types from the database
   *
   * @return array
   */
  protected function getTypes() : array {
    $query = $this->database->select('la_pills_analytics_action', 'a');
    $query->fields('a', ['type']);
    $query->orderBy('type');
    $result = $query->distinct()->execute();

    return $result->fetchCol();
  }

  /**
   * Fetches and returns user options based on collected activity from the database
   *
   * @return array
   */
  protected function getUserOptions() : array {
    $query = $this->database->select('la_pills_analytics_action', 'a');
    $query->leftJoin('users_field_data', 'ufd', 'a.user_id = ufd.uid');
    $query->fields('a', ['user_id']);
    $query->fields('ufd', ['name']);
    $query->isNotNull('a.user_id');
    $result = $query->distinct()->execute();

    // This is a special case that will later be converted to isNull
    $options = [
      0 => $this->t('- Anonymous user -'),
    ];
    $options = array_merge($options, $result->fetchAllKeyed(0, 1));

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setMethod('GET');

    $request = $this->getRequest();

    $form['inputs'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['well'],
      ],
      '#attached' => [
        'library' => [
          'core/drupal.form',
        ],
      ],
    ];

    $types = $this->getTypes();
    $form['inputs']['types'] = [
      '#type' => 'select',
      '#title' => $this->t('Types'),
      '#options' => array_combine($types, $types),
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#value' => $request->get('types'),
    ];
    $form['inputs']['users'] = [
      '#type' => 'select',
      '#title' => $this->t('Users'),
      '#options' => $this->getUserOptions(),
      '#multiple' => TRUE,
      '#required' => FALSE,
      '#value' => $request->get('users'),
    ];
    $form['inputs']['from'] = [
      '#type' => 'date',
      '#title' => $this->t('From date'),
      '#required' => FALSE,
      '#default_value' => '',
      '#value' => $request->get('from'),
    ];
    $form['inputs']['until'] = [
      '#type' => 'date',
      '#title' => $this->t('Until date'),
      '#required' => FALSE,
      '#default_value' => '',
      '#value' => $request->get('until'),
    ];
    $form['inputs']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
    ];
    $form['inputs']['reset'] = [
      '#type' => 'link',
      '#title' => $this->t('Reset'),
      '#url' => Url::fromRoute('la_pills_analytics.reports_controller_list'),
      '#attributes' => [
        'class' => [
          'button', 'btn', 'btn-danger',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Should not submit
  }

}
