<?php

namespace Drupal\la_pills_timer\Entity;

use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the LA Pills Timer entity.
 *
 * @ingroup la_pills_timer
 *
 * @ContentEntityType(
 *   id = "la_pills_timer_entity",
 *   label = @Translation("LA Pills Timer"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\la_pills_timer\LaPillsTimerEntityListBuilder",
 *     "views_data" = "Drupal\la_pills_timer\Entity\LaPillsTimerEntityViewsData",
 *   "form" = {
 *       "default" = "Drupal\la_pills_timer\Form\LaPillsTimerEntityForm",
 *       "add" = "Drupal\la_pills_timer\Form\LaPillsTimerEntityForm",
 *       "edit" = "Drupal\la_pills_timer\Form\LaPillsTimerEntityForm",
 *       "delete" = "Drupal\la_pills_timer\Form\LaPillsTimerEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\la_pills_timer\LaPillsTimerEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\la_pills_timer\LaPillsTimerEntityAccessControlHandler",
 *   },
 *   base_table = "la_pills_timer_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer la pills timer entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/la_pills_timer_entity/{la_pills_timer_entity}",
 *     "add-form" = "/admin/structure/la_pills_timer_entity/add",
 *     "edit-form" = "/admin/structure/la_pills_timer_entity/{la_pills_timer_entity}/edit",
 *     "delete-form" = "/admin/structure/la_pills_timer_entity/{la_pills_timer_entity}/delete",
 *     "collection" = "/admin/structure/la_pills_timer_entity",
 *   },
 *   field_ui_base_route = "la_pills_timer_entity.settings"
 * )
 */
class LaPillsTimerEntity extends ContentEntityBase implements LaPillsTimerEntityInterface {

  use EntityChangedTrait;
  use EntityPublishedTrait;

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage_controller, array &$values) {
    parent::preCreate($storage_controller, $values);
    $values += [
      'user_id' => \Drupal::currentUser()->id(),
      'status' => TRUE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public static function preDelete(EntityStorageInterface $storage, array $entities) {
    parent::preDelete($storage, $entities);
    // TODO See if this could just be removed
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
   * {@inheritdoc}
   */
  public function isOwner(AccountInterface $account) {
    return $this->getOwnerId() === $account->id();
  }

  /**
   * Getting a group of the current timer.
   */
  public function getTimerGroup() {
    return $this->get('group')->value;
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

    $fields['name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Name'))
      ->setDescription(t('The name of the LA Pills Timer.'))
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

    $fields['status']->setDescription(t('A boolean indicating whether the LA Pills Timer is published.'))
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
