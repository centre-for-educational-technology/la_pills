<?php

namespace Drupal\la_pills_timer\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\UserInterface;

/**
 * Defines the LA Pills Session Timer entity.
 *
 * @ingroup la_pills_timer
 *
 * @ContentEntityType(
 *   id = "la_pills_session_timer_entity",
 *   label = @Translation("LA Pills Session Timer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\la_pills_timer\LaPillsSessionTimerEntityListBuilder",
 *     "views_data" = "Drupal\la_pills_timer\Entity\LaPillsSessionTimerEntityViewsData",
 *     "access" = "Drupal\la_pills_timer\LaPillsTimerEntityAccessControlHandler",
 *   },
 *   base_table = "la_pills_session_timer_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer la pills timer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "tid" = "timer_id",
 *     "sid" = "session_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 * )
 */
class LaPillsSessionTimerEntity extends ContentEntityBase implements LaPillsSessionTimerEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'status' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);

    foreach ($entities as $entity) {
      $sessions_query = \Drupal::entityQuery('la_pills_timer_session_entity')
      ->condition('timer_id', $entity->id());

      $sessions_ids = $sessions_query->execute();

      if ($sessions_ids) {
        $sessions = \Drupal::entityTypeManager()
          ->getStorage('la_pills_timer_session_entity')
          ->loadMultiple($sessions_ids);

        foreach ($sessions as $session) {
          $session->delete();
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getName() {
    return $this->get('name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setName($name) {
    $this->set('name', $name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getStatus() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getCreatedTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function setCreatedTime($timestamp) {
    $this->set('created', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwner() {
    return $this->get('user_id')->entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getOwnerId() {
    return $this->get('user_id')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwnerId($uid) {
    $this->set('user_id', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setOwner(UserInterface $account) {
    $this->set('user_id', $account->id());
    return $this;
  }

  /**
   * Returns data gathering session
   *
   * @return Drupal\la_pills\Entity\SessionEntity
   *   Data gathering session object
   */
  public function getSession() {
    return $this->get('session_id')->entity;
  }

  /**
   * Returns data gathering session identifier
   *
   * @return int
   *   Data gathering session identifier
   */
  public function getSessionId() {
    return $this->get('session_id')->target_id;
  }

  /**
   * Getting a group of the current timer.
   */
  public function getTimerGroup() {
    return $this->get('group')->value;
  }

  /**
   * Get all sessions current timer.
   *
   * @return array|int
   */
  public function getSessionsIds() {
    $sessions = \Drupal::entityQuery('la_pills_timer_session_entity')
      ->condition('timer_id', $this->id());

    return $sessions->execute();
  }

  /**
   * Get duration current timer.
   *
   * @return int
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function getDuration() {
    $duration = 0;

    $timer_sessions_ids = $this->getSessionsIds();

    if ($timer_sessions_ids) {
      $timer_sessions = $this->entityTypeManager()
        ->getStorage('la_pills_timer_session_entity')
        ->loadMultiple($timer_sessions_ids);

      if ($timer_sessions) {

        foreach ($timer_sessions as $timer_session) {
          $duration += $timer_session->getDuration();
        }
      }
    }

    return $duration;
  }

  /**
   * @inheritdoc
   */
  public function startSession() {
    $timer_session = $this->entityTypeManager()
      ->getStorage('la_pills_timer_session_entity')
      ->create(['timer_id' => $this->id(), 'session_id' => $this->getSessionId(),]);
    $timer_session->save();

    $this->set('status', TRUE);

    return $this;
  }

  /**
   * @inheritdoc
   */
  public function stopSession() {
    $session_query = \Drupal::entityQuery('la_pills_timer_session_entity')
      ->condition('timer_id', $this->id())
      ->sort('created', 'DESC')
      ->range(0, 1);

    $last_session = $session_query->execute();

    $timer_session = $this->entityTypeManager()
      ->getStorage('la_pills_timer_session_entity')
      ->load(key($last_session));

    $timer_session->stopSession();
    $timer_session->save();
    $this->set('status', FALSE);

    return $this;
  }

  /**
   * Returns duration for currently active session or zero
   *
   * @return int
   *   Duration or 0
   */
  public function getCurrentDuration() {
    $duration = 0;

    if ($this->getStatus()) {
      $session_query = \Drupal::entityQuery('la_pills_timer_session_entity')
        ->condition('timer_id', $this->id())
        ->sort('created', 'DESC')
        ->range(0, 1);

      $last_session = $session_query->execute();

      $timer_session = $this->entityTypeManager()
        ->getStorage('la_pills_timer_session_entity')
        ->load(key($last_session));

      $duration = $timer_session->getDuration();
    }

    return $duration;
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the LA Pills Timer.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'entity_reference_autocomplete',
        'weight' => 5,
        'settings' => [
          'match_operator' => 'CONTAINS',
          'size' => '60',
          'autocomplete_type' => 'tags',
          'placeholder' => '',
        ],
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['timer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Timer'))
      ->setDescription(t('The timer copied from.'))
      ->setSetting('target_type', 'la_pills_timer_entity')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setDescription(t('The Session Entity timer belongs to.'))
      ->setSetting('target_type', 'session_entity')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the LA Pills Session Timer.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['group'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Group'))
      ->setDescription(t('The group of the timer.'))
      ->setStorageRequired(FALSE)
      ->setSettings([
        'allowed_values' => [
          'student' => 'Student',
          'teacher' => 'Teacher',
          'other' => 'Other',
        ],
      ])
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'list_default',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 2,
      ])
      ->setDefaultValue('other')
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['color'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Color'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('#ffffff')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE);

    $fields['status']->setDescription(t('A boolean indicating whether the LA Pills Session Timer is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 4,
      ]);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
