<?php


namespace Drupal\la_pills;


interface SessionManagerInterface
{
  /**
   * Checks if internal session identifier is present.
   *
   * @return bool
   */
  public function hasSessionId(): bool;

  /**
   * Returns unique internal session identifier or creates one if not yet present.
   *
   * @return string Unique internal session identifier
   */
  public function getSessionId(): string;
}
