<?php

namespace Drupal\la_pills\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\la_pills\FetchClass\SessionTemplate;

/**
 * Provides an interface for defining LA Pills Session entities.
 *
 * @ingroup la_pills
 */
interface SessionEntityInterface extends ContentEntityInterface, EntityChangedInterface, EntityOwnerInterface {

  /**
   * Gets the LA Pills Session name.
   *
   * @return string
   *   Name of the LA Pills Session.
   */
  public function getName();

  /**
   * Sets the LA Pills Session name.
   *
   * @param string $name
   *   The LA Pills Session name.
   *
   * @return \Drupal\la_pills\Entity\SessionEntityInterface
   *   The called LA Pills Session entity.
   */
  public function setName($name);

  /**
   * Gets the LA Pills Session creation timestamp.
   *
   * @return int
   *   Creation timestamp of the LA Pills Session.
   */
  public function getCreatedTime();

  /**
   * Sets the LA Pills Session creation timestamp.
   *
   * @param int $timestamp
   *   The LA Pills Session creation timestamp.
   *
   * @return \Drupal\la_pills\Entity\SessionEntityInterface
   *   The called LA Pills Session entity.
   */
  public function setCreatedTime($timestamp);

  /**
   * Returns the LA Pills Session published status indicator.
   *
   * Unpublished LA Pills Session are only visible to restricted users.
   *
   * @return bool
   *   TRUE if the LA Pills Session is published.
   */
  public function isPublished();

  /**
   * Sets the published status of a LA Pills Session.
   *
   * @param bool $published
   *   TRUE to set this LA Pills Session to published, FALSE to set it to unpublished.
   *
   * @return \Drupal\la_pills\Entity\SessionEntityInterface
   *   The called LA Pills Session entity.
   */
  public function setPublished($published);

  /**
   * Returns active status indicator.
   *
   * @return bool TRUE is entity is active
   */
  public function isActive();

  /**
   * Sets the active status of an entity.
   *
   * @param bool $active
   *   TRUE to set entity as active, FALSE to set it as inactive.
   *
   * @return \Drupal\la_pills\Entity\SessionEntityInterface
   *   Entity object
   */
  public function setActive($active);

  /**
   * Returns the value for ananymous users being allowed to participate.
   *
   * @return bool TRUE is anonymous users are allowed
   */
  public function getAllowAnonymousResponses();

  /**
   * Sets the value for ananymous users being allowed to participate.
   *
   * @param bool $allow_anonymous_responses
   *   TRUE to set as allowed, FALSE to set it as disallowed.
   *
   * @return \Drupal\la_pills\Entity\SessionEntityInterface
   *   Entity object
   */
  public function setAllowAnonymousRespones($allow_anonymous_responses);

  /**
   * Determines if account is the owner of an entity.
   *
   * @param Drupal\Core\Session\AccountInterface $account
   *   User account
   *
   * @return bool
   *   TRUE if owner, FLSE if not
   */
  public function isOwner(AccountInterface $account);

  /**
   * Returns chosen Session Template
   *
   * @return Drupal\la_pills\FetchClass\SessionTemplate
   *   Session Template
   */
  public function getSessionTemplate();

  /**
   * Returns chosen Session Template data
   *
   * @return array
   *   Session Template data structure
   */
  public function getSessionTemplateData();

  /**
   * Returns unique numeric code value.
   *
   * @return string
   *   Unique numeric coe value
   */
  public function getCode();

  /**
   * Returns the value for name being required in case of anonymous submission.
   *
   * @return bool
   *   TRUE if required, FALSE if not
   */
  public function getRequireName();

  /**
   * Sets value for name being requred in case of anonymous submissions.
   *
   * @param bool $require_name
   *   TRUE to set as rquired, FALSE to set as not required.
   *
   * @return \Drupal\la_pills\Entity\SessionEntityInterface
   *   Entity object
   */
  public function setRequireName($require_name);

}
