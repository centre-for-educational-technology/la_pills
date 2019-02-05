<?php

namespace Drupal\la_pills\Entity;

use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityChangedTrait;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\user\UserInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines the LA Pills Session entity.
 *
 * @ingroup la_pills
 *
 * @ContentEntityType(
 *   id = "session_entity",
 *   label = @Translation("LA Pills Session"),
 *   handlers = {
 *     "view_builder" = "Drupal\Core\Entity\EntityViewBuilder",
 *     "list_builder" = "Drupal\la_pills\SessionEntityListBuilder",
 *     "views_data" = "Drupal\la_pills\Entity\SessionEntityViewsData",
 *
 *     "form" = {
 *       "default" = "Drupal\la_pills\Form\SessionEntityForm",
 *       "add" = "Drupal\la_pills\Form\SessionEntityForm",
 *       "edit" = "Drupal\la_pills\Form\SessionEntityForm",
 *       "delete" = "Drupal\la_pills\Form\SessionEntityDeleteForm",
 *       "questionnaire" = "Drupal\la_pills\Form\SessionEntityQuestionnaireForm"
 *     },
 *     "access" = "Drupal\la_pills\SessionEntityAccessControlHandler",
 *     "route_provider" = {
 *       "html" = "Drupal\la_pills\SessionEntityHtmlRouteProvider",
 *     },
 *   },
 *   base_table = "session_entity",
 *   admin_permission = "administer la pills session entities",
 *   entity_keys = {
 *     "id" = "id",
 *     "label" = "name",
 *     "uuid" = "uuid",
 *     "uid" = "user_id",
 *     "langcode" = "langcode",
 *     "status" = "status",
 *     "active" = "active",
 *     "template" = "template",
 *   },
 *   links = {
 *     "canonical" = "/admin/structure/session_entity/{session_entity}",
 *     "add-form" = "/admin/structure/session_entity/add",
 *     "edit-form" = "/admin/structure/session_entity/{session_entity}/edit",
 *     "delete-form" = "/admin/structure/session_entity/{session_entity}/delete",
 *     "collection" = "/admin/structure/session_entity",
 *     "dashboard" = "/admin/structure/session_entity/{session_entity}/dashboard",
 *     "questionnaire" = "/admin/structure/session_entity/{session_entity}/questionnaire/{questionnaire_uuid}",
 *   },
 *   field_ui_base_route = "session_entity.settings"
 * )
 */
class SessionEntity extends ContentEntityBase implements SessionEntityInterface {

  use EntityChangedTrait;

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
   * Removes answers for deleted Session entities.
   * @param  array  $entities An array of SessionEntity objects
   * @return int              Count of removed answers
   */
  protected static function removeAnswersOnDelete(array $entities) {
    $connection = \Drupal::service('database');

    return $connection->delete('session_questionnaire_answer')
    ->condition('session_entity_uuid', array_map(function($entity) {
      return $entity->uuid();
    }, $entities), 'IN')
    ->execute();
  }

  /**
   * {@inheritdoc}
   */
  public static function postDelete(EntityStorageInterface $storage_controller, array $entities) {
    parent::postDelete($storage_controller, $entities);
    static::removeAnswersOnDelete($entities);
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
  public function isOwner(AccountInterface $account) {
    return $this->getOwnerId() === $account->id();
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
  public function isPublished() {
    return (bool) $this->getEntityKey('status');
  }

  /**
   * {@inheritdoc}
   */
  public function setPublished($published) {
    $this->set('status', $published ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function isActive() {
    return (bool) $this->getEntityKey('active');
  }

  /**
   * {@inheritdoc}
   */
  public function setActive($active) {
    $this->set('active', $active ? TRUE : FALSE);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionTemplateData() {
    $manager = \Drupal::service('la_pills.sesion_template_manager');
    return $manager->getTemplate($this->getEntityKey('template'))->getData();
  }

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields = parent::baseFieldDefinitions($entity_type);

    $fields['user_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Authored by'))
      ->setDescription(t('The user ID of author of the LA Pills Session entity.'))
      ->setRevisionable(TRUE)
      ->setSetting('target_type', 'user')
      ->setSetting('handler', 'default')
      ->setTranslatable(TRUE)
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
      ->setDescription(t('The name of the LA Pills Session entity.'))
      ->setSettings([
        'max_length' => 50,
        'text_processing' => 0,
      ])
      ->setDefaultValue('')
      ->setDisplayOptions('view', [
        'label' => 'above',
        'type' => 'string',
        'weight' => -4,
      ])
      ->setDisplayOptions('form', [
        'type' => 'string_textfield',
        'weight' => -4,
      ])
      ->setDisplayConfigurable('form', TRUE)
      ->setDisplayConfigurable('view', TRUE)
      ->setRequired(TRUE);

    $fields['status'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Publishing status'))
      ->setDescription(t('A boolean indicating whether the LA Pills Session is published.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -2,
      ]);

    $fields['active'] = BaseFieldDefinition::create('boolean')
      ->setLabel(t('Availability to participants'))
      ->setDescription(t('A boolean indicating whether the LA Pills Session is active or inactive. Making session inactive prevents participants from interacting with the session, everything is read-only.'))
      ->setDefaultValue(TRUE)
      ->setDisplayOptions('form', [
        'type' => 'boolean_checkbox',
        'weight' => -1,
      ]);

      $fields['template'] = BaseFieldDefinition::create('list_string')
        ->setLabel(t('Session template'))
        ->setDescription(t('Choose what kind of session it will be'))
        ->setSettings([
          'max_length' => 50,
          'text_processing' => 0,
          'allowed_values_function' => '_la_pills_session_template_allowed_values',
        ])
        ->setDefaultValue('')
        ->setDisplayOptions('view', [
          'label' => 'above',
          'type' => 'string',
          'weight' => -3,
        ])
        ->setDisplayOptions('form', [
          'type' => 'options_buttons',
          'weight' => -3,
        ])
        ->setDisplayConfigurable('form', TRUE)
        ->setDisplayConfigurable('view', TRUE)
        ->setRequired(TRUE);

    $fields['created'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Created'))
      ->setDescription(t('The time that the entity was created.'));

    $fields['changed'] = BaseFieldDefinition::create('changed')
      ->setLabel(t('Changed'))
      ->setDescription(t('The time that the entity was last edited.'));

    return $fields;
  }

}
