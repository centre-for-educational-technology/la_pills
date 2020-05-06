<?php

namespace Drupal\la_pills_analytics;

use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * Interface AnalyticsManagerInterface.
 */
interface AnalyticsManagerInterface {

  /**
   * Returns basic data for entity that can be stored in the "data" column.
   * Only inscludes id, uuid, type and label.
   *
   * @param  EntityInterface $entity
   *   Entity object
   *
   * @return array
   *   Basic data
   */
  public function getEntityData(EntityInterface $entity) : array;

  /**
   * Store action data in the database. Certain column values are extracted from
   * the request.
   *
   * @param string  $type
   *   Action type
   * @param Request $request
   *   Request object
   * @param array   $data
   *   Data to store in the "data" column
   */
  public function storeAction(string $type, Request $request, array $data = []) : void;

  /**
   * Store view action in the database.
   *
   * @param Request $request
   *   Request object
   * @param array   $data
   *   Data to store in the "data" column
   */
  public function storeView(Request $request, array $data = []) : void;

  /**
   * Stores entity action the the database. Path value is set to an entity
   * internal path instead of the one from request. Title is set to entity
   * label value. Data column will have "entity" key added automatically
   * with basic entity data.
   *
   * @param EntityInterface $entity
   *   Entity object
   * @param string          $type
   *   Action type
   * @param Request         $request
   *   Request object
   * @param array           $data
   *   Data to store in the "data" column
   */
  public function storeEntityAction(EntityInterface $entity, string $type, Request $request, array $data = []) : void;

}
