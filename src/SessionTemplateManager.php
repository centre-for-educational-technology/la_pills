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
      $tmp = $result->fetchAll();

      $templates = array_combine(array_column($tmp, 'uuid'), $tmp);
    }

    return $templates;
  }

  /**
   * {@inheritdoc}
   */
  public function getTemplate(string $uuid) {
    $templates = $this->getTemplates();

    return (isset($templates[$uuid])) ? $templates[$uuid] : NULL;
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

  /**
   * {@inheritdoc}
   */
  public function validateTemplate(array $structure) {
    $errors = [];

    if (isset($structure['context']) && is_array($structure['context']) && count($structure['context']) > 0) {
      foreach (['title', 'date', 'course', 'program', 'description', 'technologies',] as $key) {
        if (!array_key_exists($key, $structure['context'])) {
          $errors[] = t('<strong>Context</strong> is missing a key <strong>@key</key>!', ['@key' => $key,]);
        }
      }
    } else {
      $errors[] = t('Root key <strong>@key</strong> is missing, not an array or empty!', ['@key' => 'context']);
    }

    foreach (['goals', 'activities', 'questions', 'questionnaires',] as $key) {
      if (!(isset($structure[$key]) && is_array($structure[$key]) && count($structure[$key]) > 0)) {
        $errors[] = t('Root key <strong>@key</strong> is missing, not an array or empty!', ['@key' => $key,]);
      }
    }

    return $errors;
  }

}
