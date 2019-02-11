<?php

namespace Drupal\la_pills\Entity;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;
use Drupal\user\EntityOwnerInterface;
use Drupal\Core\Session\AccountInterface;

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
   * @return array
   *   Session Template data structure
   */
  public function getSessionTemplateData();

}
