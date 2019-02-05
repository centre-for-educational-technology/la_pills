<?php

namespace Drupal\la_pills;

use Drupal\Core\Database\Connection;
use Drupal\Component\Uuid\Php as Uuid;

/**
 * Class SessionTemplateManager.
 */
class SessionTemplateManager implements SessionTemplateManagerInterface {

  /**
   * Session template table
   * @var string
   */
  const SESSION_TEMPLATE_TABLE = 'session_template';

  /**
   * Session template class
   * @var string
   */
  const SESSION_TEMPLATE_FETCH_CLASS = 'Drupal\la_pills\FetchClass\SessionTemplate';

  /**
   * Database connection
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Uuid service
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
    $query = $this->connection->select(self::SESSION_TEMPLATE_TABLE, 'st', [
      'fetch' => self::SESSION_TEMPLATE_FETCH_CLASS,
    ]);
    $query->fields('st', ['uuid', 'data',]);

    $result = $query->execute();

    return $result->fetchAll();
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(string $uuid) {
    $query = $this->connection->select(self::SESSION_TEMPLATE_TABLE, 'st', [
      'fetch' => self::SESSION_TEMPLATE_FETCH_CLASS,
    ]);
    $query->fields('st', ['uuid', 'data',]);
    $query->condition('st.uuid', $uuid, '=');

    // TODO Need to add a handler that will check if sessions exists
    $result = $query->execute();

    return $result->fetch();
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
