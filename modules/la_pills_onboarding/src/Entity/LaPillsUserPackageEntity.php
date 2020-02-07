<?php

namespace Drupal\la_pills_onboarding\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityPublishedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
 * Defines the User package entity.
 *
 * @ingroup la_pills_onboarding
 *
 * @ContentEntityType(
 *   id = "la_pills_user_package",
 *   label = @Translation("User package"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\la_pills_onboarding\LaPillsUserPackageEntityListBuilder",
 *     "views_data" = "Drupal\la_pills_onboarding\Entity\LaPillsUserPackageEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\la_pills_onboarding\Form\LaPillsUserPackageEntityForm",
 *       "add" = "Drupal\la_pills_onboarding\Form\LaPillsUserPackageEntityForm",
 *       "edit" = "Drupal\la_pills_onboarding\Form\LaPillsUserPackageEntityForm",
 *       "delete" = "Drupal\la_pills_onboarding\Form\LaPillsUserPackageEntityDeleteForm",
 *     },
 *     "route_provider" = {
 *       "html" = "Drupal\la_pills_onboarding\LaPillsUserPackageEntityHtmlRouteProvider",
 *     },
 *     "access" = "Drupal\la_pills_onboarding\LaPillsUserPackageEntityAccessControlHandler",
 *   },
 *   base_table = "la_pills_user_package",
 *   translatable = FALSE,
 *   admin_permission = "administer user package entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "published" = "status",
 *   },
 *   links = {
 *     "canonical" = "/onboarding/package/la_pills_user_package/{la_pills_user_package}",
 *     "add-form" = "/onboarding/package/la_pills_user_package/add",
 *     "edit-form" = "/onboarding/package/la_pills_user_package/{la_pills_user_package}/edit",
 *     "delete-form" = "/onboarding/package/la_pills_user_package/{la_pills_user_package}/delete",
 *     "collection" = "/onboarding/package/la_pills_user_package",
 *   },
 *   field_ui_base_route = "la_pills_user_package.settings"
 * )
 */
class LaPillsUserPackageEntity extends ContentEntityBase implements LaPillsUserPackageEntityInterface {

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
  public function getQuestionsEntities() : array {
    return $this->get('questions')->referencedEntities();
  }

  /**
   * {@inheritdoc}
   */
  public function getActivitiesEntities() : array {
    return $this->get('activities')->referencedEntities();
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
      ->setDescription(t('The user ID of author of the User package entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setDisplayOptions('view', [
        'label' => 'hidden',
        'type' => 'author',
        'weight' => 5,
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
      ->setDescription(t('The name of the User package entity.'))
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

    $fields['description'] = BaseFieldDefinition::create('string_long')
      ->setLabel(t('Description'))
      ->setDescription(t('The description of the package.'))
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'basic_string',
        'weight' => 2,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textarea',
        'weight' => 2,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(FALSE);

    $fields['status']->setDescription(t('A boolean indicating whether the User package is published.'))
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => 5,
      ]);

    $fields['questions'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Quick feedback questions'))
      ->setDescription(t('Quick feedback question identifiers.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'la_pills_question_entity')
      ->setSetting('handler', 'entity_owner')
      ->setDisplayOptions('view', [
        'label' => 'label',
        'type' => 'entity_reference_label',
        'weight' => 3,
        'settings' => [
          'link' => FALSE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 3,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['activities'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Activity log activities'))
      ->setDescription(t('Activity log activity identifiers.'))
      ->setRevisionable(FALSE)
      ->setSetting('target_type', 'la_pills_timer_entity')
      ->setSetting('handler', 'entity_owner')
      ->setDisplayOptions('view', [
        'label' => 'label',
        'type' => 'entity_reference_label',
        'weight' => 4,
        'settings' => [
          'link' => FALSE,
        ],
      ])
      ->setDisplayOptions('form', [
        'type' => 'options_select',
        'weight' => 4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setCardinality(FieldStorageDefinitionInterface::CARDINALITY_UNLIMITED);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
