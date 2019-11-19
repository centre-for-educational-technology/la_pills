<?php

namespace Drupal\la_pills_quick_feedback\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;

/**
 * Defines the LaPills Question Entity entity.
 *
 * @ingroup la_pills_quick_feedback
 *
 * @ContentEntityType(
 *   id = "la_pills_question_entity",
 *   label = @Translation("LaPills Question Entity"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\la_pills_quick_feedback\LaPillsQuestionEntityListBuilder",
 *     "views_data" = "Drupal\la_pills_quick_feedback\Entity\LaPillsQuestionEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\la_pills_quick_feedback\Form\LaPillsQuestionEntityForm",
 *       "add" = "Drupal\la_pills_quick_feedback\Form\LaPillsQuestionEntityForm",
 *       "edit" = "Drupal\la_pills_quick_feedback\Form\LaPillsQuestionEntityForm",
 *       "delete" = "Drupal\la_pills_quick_feedback\Form\LaPillsQuestionEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\la_pills_quick_feedback\LaPillsQuestionEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\la_pills_quick_feedback\LaPillsQuestionEntityAccessControlHandler",
 *   },
 *   base_table = "la_pills_question_entity",
 *   translatable = FALSE,
 *   admin_permission = "administer lapills question entity entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "prompt",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *     "icon" = "icon",
 *     "short_name" = "short_name",
 *     "type" = "type",
 *     "data" = "data",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/la_pills_question_entity/{la_pills_question_entity}",
 *     "add-form" = "/admin/structure/la_pills_question_entity/add",
 *     "edit-form" = "/admin/structure/la_pills_question_entity/{la_pills_question_entity}/edit",
 *     "delete-form" = "/admin/structure/la_pills_question_entity/{la_pills_question_entity}/delete",
 *     "collection" = "/admin/structure/la_pills_question_entity",
 *   },
 *   field_ui_base_route = "la_pills_question_entity.settings"
 * )
 */
class LaPillsQuestionEntity extends ContentEntityBase implements LaPillsQuestionEntityInterface {

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
  public function isActive(bool $buypass_cache = FALSE) {
    $manager = \Drupal::service('la_pills_quick_feedback.question_manager');

    return $manager->isActiveQuestion($this, $buypass_cache);
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    // Add the published field.
    $fields += static::publishedBaseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the LaPills Question Entity entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 10,
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

    $fields['icon'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Icon'))
      ->setDescription(t('Choose a suitable icon.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
        'allowed_values_function' => '_la_pills_quick_feedback_icon_allowed_values',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 0,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 0,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['short_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Short name'))
      ->setDescription(t('The short name of the LaPills Question Entity entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 1,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 1,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['prompt'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Prompt'))
      ->setDescription(t('The prompt of the LaPills Question Entity entity.'))
      ->setSettings([
        'max_length' => 255,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the LaPills Question Entity entity.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 3,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['type'] = BaseFieldDefinition::create('list_string')
      ->setLabel(t('Question type'))
      ->setDescription(t('Choose what type of question would it be.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
        'allowed_values_function' => '_la_pills_quick_feedback_question_type_allowed_values',
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => 4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['data'] = BaseFieldDefinition::create('map')
      ->setLabel('Question')
      ->setDescription('Serialized question data.');

    $fields['status']->setDescription(t('A boolean indicating whether the LaPills Question Entity is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 5,
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
