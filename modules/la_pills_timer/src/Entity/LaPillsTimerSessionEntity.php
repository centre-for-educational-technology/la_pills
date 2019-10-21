<?php

namespace Drupal\la_pills_timer\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the La Pills Timer Session entity.
 *
 * @ingroup la_pills_timer
 *
 * @ContentEntityType(
 *   id = "la_pills_timer_session_entity",
 *   label = @Translation("La Pills Timer Session"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\la_pills_timer\LaPillsTimerSessionEntityListBuilder",
 *     "views_data" = "Drupal\la_pills_timer\Entity\LaPillsTimerSessionEntityViewsData",
 *
 *     "access" = "Drupal\la_pills_timer\LaPillsTimerSessionEntityAccessControlHandler",
 *   },
 *   base_table = "la_pills_timer_session_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer la pills timer session entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "uuid" = "uuid",
 *     "tid" = "timer_id",
 *     "sid" = "session_id",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 * )
 */
class LaPillsTimerSessionEntity extends ContentEntityBase implements LaPillsTimerSessionEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getStartTime() {
    return $this->get('created')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getStopTime() {
    return $this->get('stop')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return $this->get('status')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getDuration() {
    if ($this->get('duration')->value) {
      return $this->get('duration')->value;
    } else {
      return $this->calculateDuration(time());
    }
  }

  /**
   * {@inheritdoc}
   */
  public function stopSession() {
    $timestamp = time();
    $this->set('stop', $timestamp);
    $this->set('duration', $this->calculateDuration($timestamp));

    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function calculateDuration($stop = NULL) {
    $start = $this->get('created')->value;

    return $stop - $start;
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
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['timer_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Timer'))
      ->setDescription(t('The parent session timer.'))
      ->setSetting('target_type', 'la_pills_session_timer_entity')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['session_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Session'))
      ->setDescription(t('The Session Entity timer belongs to.'))
      ->setSetting('target_type', 'session_entity')
      ->setReadOnly(TRUE)
      ->setRequired(TRUE);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the La Pills Timer Session entity.'))
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

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['stop'] = BaseFieldDefinition::create('timestamp')
      ->setLabel(t('Timer session stop'))
      ->setDescription(t('Stopping the timer session.'));

    // TODO It might make sense to use an integer field for this
    $fields['duration'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Duration'))
      ->setDescription(t('Timer session duration.'));

    return $fields;
  }

}
