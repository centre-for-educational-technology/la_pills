<?php

namespace Drupal\la_pills\FetchClass;

class SessionTemplate {
  /**
   * Holds unserialized data once is requested so that processing is done once at most
   * @var array
   */
  private $processedData;

  /**
   * Returns the data structure form Session tempate
   * @return array
   */
  public function getData() {
    if ($this->processedData === NULL) {
      $this->processedData = unserialize($this->data);;
    }

    return $this->processedData;
  }
}
