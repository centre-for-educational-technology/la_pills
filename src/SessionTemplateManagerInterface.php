<?php

namespace Drupal\la_pills;

/**
 * Interface SessionTemplateManagerInterface.
 */
interface SessionTemplateManagerInterface {

  /**
   * Returns an array of all available session templates.
   * @return array
   */
  public function getTemplates();

  /**
   * Returns single session template object.
   * @param  string $uuid Session template unique identifier
   * @return Drupal\la_pills\FetchClass\SessionTemplate
   */
  public function getTemplate(string $uuid);

  /**
   * Processed parsed template data and inserts into the database
   * @param array $structure Temolate structure
   */
  public function addTemplate(array $structure);

}
