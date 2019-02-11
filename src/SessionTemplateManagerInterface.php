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
   * @return Drupal\la_pills\FetchClass\SessionTemplate
   *   Session Template
   */
  public function getTemplate(string $uuid);

  /**
   * Processed parsed template data and inserts into the database
   *
   * @param array $structure
   *   Temolate structure
   */
  public function addTemplate(array $structure);

}
