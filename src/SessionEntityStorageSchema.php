<?php

namespace Drupal\la_pills;

use Drupal\Core\Entity\ContentEntityTypeInterface;
use Drupal\Core\Entity\Sql\SqlContentEntityStorageSchema;
use Drupal\Core\Field\FieldStorageDefinitionInterface;

/**
* Defines the SessionEntity schema handler.
*/
class SessionEntityStorageSchema extends SqlContentEntityStorageSchema {

  /**
  * {@inheritdoc}
  */
  protected function getSharedTableFieldSchema(FieldStorageDefinitionInterface $storage_definition, $table_name, array $column_mapping) {
    $schema = parent::getSharedTableFieldSchema($storage_definition, $table_name, $column_mapping);
    $field_name = $storage_definition->getName();

    if ($table_name == 'session_entity') {
      switch ($field_name) {
        case 'code':
        $this->addSharedTableFieldIndex($storage_definition, $schema, TRUE);
        // XXX This can not be used if any entities already exist
        //$this->addSharedTableFieldUniqueKey($storage_definition, $schema, TRUE);
      }
    }

    return $schema;
  }

}
