<?php

namespace Drupal\la_pills;

use Drupal\Core\Database\Connection;
use Drupal\Component\Uuid\Php as Uuid;
use Drupal\la_pills\FetchClass\SessionTemplate;

/**
 * Class SessionTemplateManager.
 */
class SessionTemplateManager implements SessionTemplateManagerInterface {

  /**
   * Session template table
   */
  const SESSION_TEMPLATE_TABLE = 'session_template';

  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Uuid service
   *
   * @var Drupal\Component\Uuid\Php
   */
  protected $uuid;

  /**
   * Constructs a new SessionTemplateManager object.
   */
  public function __construct(Connection $connection, Uuid $uuid) {
    $this->connection = $connection;
    $this->uuid = $uuid;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplates() {
    $templates = &drupal_static(__METHOD__);

    if (!isset($templates)) {
      $query = $this->connection->select(self::SESSION_TEMPLATE_TABLE, 'st', [
        'fetch' => SessionTemplate::class,
      ]);
      $query->fields('st', ['uuid', 'data',]);

      $result = $query->execute();

      $templates = $result->fetchAll();
    }

    return $templates;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(string $uuid) {
    // TODO See if it would make sense to always fetch all the templates and then extract the required one
    // This would make sure that there are no duplicates
    $templates = &drupal_static(__METHOD__);

    if (!isset($templates[$uuid])) {
      $query = $this->connection->select(self::SESSION_TEMPLATE_TABLE, 'st', [
        'fetch' => SessionTemplate::class,
      ]);
      $query->fields('st', ['uuid', 'data',]);
      $query->condition('st.uuid', $uuid, '=');

      // TODO Need to add a handler that will check if sessions exists
      $result = $query->execute();

      $templates[$uuid] = $result->fetch();
    }

    return $templates[$uuid];
  }

  /**
   * {@inheritdoc}
   */
  public function addTemplate(array $structure) {
    $structure['context']['uuid'] = $this->uuid->generate();

    foreach (['goals', 'activities', 'questions', 'questionnaires',] as $key) {
      array_walk($structure[$key], function (&$single) {
        $single['uuid'] = $this->uuid->generate();
      });

      $structure[$key] = array_combine(array_map(function ($single) {
        return $single['uuid'];
      }, $structure[$key]), $structure[$key]);
    }

    $lookup = array_combine(array_map(function($single) { return $single['title']; }, $structure['questions']), $structure['questions']);
    array_walk($structure['questionnaires'], function(&$questionnaire) use ($lookup) {
      array_walk($questionnaire['questions'], function(&$question) use ($lookup) {
        $question['uuid'] = $lookup[$question['title']]['uuid'];
      });
    });

    $this->connection->insert(self::SESSION_TEMPLATE_TABLE)
    ->fields([
      'uuid' => $structure['context']['uuid'],
      'data' => serialize($structure),
      'created' => REQUEST_TIME,
      'changed' => REQUEST_TIME,
    ])
    ->execute();
  }

}
