<?php

namespace Drupal\la_pills\FetchClass;

class SessionTemplate {

  /**
   * Holds unserialized data once is requested so that processing is done once at most
   *
   * @var array
   */
  private $processedData;

  /**
   * Returns the data structure form Session tempate
   *
   * @return array
   *   Session Template data structure
   */
  public function getData() {
    if ($this->processedData === NULL) {
      $this->processedData = unserialize($this->data);;
    }

    return $this->processedData;
  }

  /**
   * Converts question type text to lowercase and replaces spaces with dashes.
   *
   * @param  string $type
   *   Question type
   *
   * @return string
   *   Processed question type
   */
  public static function processQuestionType(string $type) {
    return str_replace(' ', '-', strtolower(trim($type)));
  }

  /**
   * Determines if session template has external dashboard set
   * @return boolean
   */
  public function hasExternalDashboard() {
    $data = $this->getData();

    return isset($data['dashboard']) && isset($data['dashboard']['url']) && $data['dashboard']['url'];
  }

}
