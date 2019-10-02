<?php

namespace Drupal\la_pills\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class DashboardFilterForm.
 */
class DashboardFilterForm extends FormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'dashboard_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form_state->setMethod('GET');

    $request = $this->getRequest();
    $from = $request->get('date_from');
    $until = $request->get('date_until');

    $form['filter'] = [
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

    if ($from && $until) {
      $form['filter']['alert'] = [
        '#type' => 'html_tag',
        '#tag' => 'div',
        '#value' => $this->t('Filter enabled. Dashboard would only use data for selected period.'),
        '#attributes' => [
          'class' => ['alert', 'alert-info',],
          'role' => 'alert',
        ],
      ];
    }

    $form['filter']['date_from'] = [
      '#type' => 'date',
      '#title' => $this->t('From date'),
      '#required' => TRUE,
      '#default_value' => '',
      '#value' => $from,
    ];
    $form['filter']['date_until'] = [
      '#type' => 'date',
      '#title' => $this->t('Until date'),
      '#required' => TRUE,
      '#default_value' => '',
      '#value' => $until,
    ];
    $form['filter']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#attributes' => [
        'class' => ['btn-primary'],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
  }

}
