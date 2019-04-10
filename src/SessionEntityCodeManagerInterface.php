<?php

namespace Drupal\la_pills;
use Drupal\la_pills\Entity\SessionEntityInterface;

/**
 * Interface SessionEntityCodeManagerInterface.
 */
interface SessionEntityCodeManagerInterface {

  /**
   * Generates a string of random numbers from 0 to 9 for a given length.
   * Source: https://stackoverflow.com/a/13169091/2704169
   *
   * @param  int    $length
   *   Length of the resulting string
   *
   * @return string
   *   Code with given length consisting of random numbers
   */
  public function generateRandomCode(int $length);

  /**
   * Checks if code is unique in the context of database and column.
   *
   * @param  string  $code
   *   Code to check for
   * @param  string  $table_name
   *   Database table to check
   * @param  string  $column_name
   *   Database table column to check
   *
   * @return boolean
   *   TRUE if unique, FALSE if not
   */
  public function isUniqueCode(string $code, string $table_name, string $column_name);

  /**
   * Generates unique code and checks if one already exists within the database.
   * NB! Code length could be different as there is a threshhold on failed
   * attempts before the length is increased.
   *
   * @param  string $table_name
   *   Database table to check
   * @param  string $column_name
   *   Database table column to check
   * @param  int    $length
   *   Desired length of the code
   *
   * @return string
   *   Generated unique numeric code
   */
  public function generateUniqueCode(string $table_name, string $column_name, int $length);

  /**
   * Checks if entity has a unique code. Determines table name based on the
   * entity provided.
   *
   * @param  Drupal\la_pills\Entity\SessionEntityInterface $entity
   *   SessionEntity instance
   * @param  string $column_name
   *   Column name within the table
   * @return boolean
   *   TRUE if is unique, FALSE otherwise
   */
  public function hasUniqueCode(SessionEntityInterface $entity, string $column_name);

}
