<?php

namespace Drupal\la_pills;
use Drupal\Core\Database\Driver\mysql\Connection;
use Drupal\la_pills\Entity\SessionEntityInterface;

/**
 * Class SessionEntityCodeManager.
 */
class SessionEntityCodeManager implements SessionEntityCodeManagerInterface {

  /**
   * Drupal\Core\Database\Driver\mysql\Connection definition.
   *
   * @var \Drupal\Core\Database\Driver\mysql\Connection
   */
  protected $database;
  /**
   * Constructs a new SessionEntityCodeManager object.
   */
  public function __construct(Connection $database) {
    $this->database = $database;
  }

  /**
   * {@inheritdoc}
   */
   public function generateRandomCode(int $length) {
     $result = '';

     for($i = 0; $i < $length; $i++) {
       $result .= (function_exists('random_int')) ? random_int(0, 9) : mt_rand(0, 9);
     }

     return $result;
   }

   /**
    * {@inheritdoc}
    */
   public function isUniqueCode(string $code, string $table_name, string $column_name) {
     $query = $this->database->select($table_name, 'bt')->condition("bt.$column_name", $code);
     $query->addExpression('COUNT(*)');
     $query->countQuery();
     $count = $query->execute()->fetchField();

     return (int)$count === 0;
   }

   /**
    * {@inheritdoc}
    */
   public function generateUniqueCode(string $table_name, string $column_name, int $length) {
     $iteration = 1;

     $code = $this->generateRandomCode($length);

     while(!$this->isUniqueCode($code, $table_name, $column_name)) {
       $code = $this->generateRandomCode($length);
       $iteration++;
       if ($iteration > 3) {
         $length++;
         $iteration = 0;
       }
     }

     return $code;
   }

   /**
    * {@inheritdoc}
    */
   public function hasUniqueCode(SessionEntityInterface $entity, string $column_name) {
     $table_name = $entity->getEntityType()->getBaseTable();

     $query = $this->database->select($table_name, 'bt')
       ->condition("bt.$column_name", $entity->getCode())
       ->condition("bt.id", $entity->id(), '<>');
     $query->addExpression('COUNT(*)');
     $query->countQuery();
     $count = $query->execute()->fetchField();

     return (int)$count === 0;
   }

}
