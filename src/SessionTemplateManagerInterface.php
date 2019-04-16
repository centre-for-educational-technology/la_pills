<?php

namespace Drupal\la_pills;

/**
 * Interface SessionTemplateManagerInterface.
 */
interface SessionTemplateManagerInterface {

  /**
   * Returns an array of all available Session Templates.
   *
   * @return array
   *   All available templates
   */
  public function getTemplates();

  /**
   * Returns single Session Template object.
   *
   * @param  string $uuid
   *   Session template unique identifier
   *
   * @return Drupal\la_pills\FetchClass\SessionTemplate|NULL
   *   Session Template or NULL if one does not exist
   */
  public function getTemplate(string $uuid);

  /**
   * Processed parsed template data and inserts into the database
   *
   * @param array $structure
   *   Template structure
   */
  public function addTemplate(array $structure);

  /**
   * Validates a template structure, checks for some of the required elements
   * being present.
   * NB! Validation is rather shallow and does not fully check the structural
   * integrity on the deepest level. Mostly checks that root level elements are
   * present and of type array with at least one element present. Context is
   * the only one checked for all the required keys being present.
   *
   * @param  array  $structure
   *   Template structure
   *
   * @return [type]            [description]
   */
  public function validateTemplate(array $structure);

}
