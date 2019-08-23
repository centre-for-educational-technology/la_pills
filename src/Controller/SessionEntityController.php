<?php

namespace Drupal\la_pills\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\la_pills\Entity\SessionEntity;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Entity\EntityInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\Component\Utility\Random;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Database\Connection;
use Drupal\la_pills\FetchClass\SessionTemplate;
use Drupal\Core\Messenger\Messenger;
use Drupal\Core\Render\Renderer;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RemoveCommand;
use Drupal\Core\Ajax\AfterCommand;
use Drupal\Core\Url;

/**
 * Class SessionEntityController.
 */
class SessionEntityController extends ControllerBase {

  /**
   * Database connection
   *
   * @var Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Messenger service
   *
   * @var Drupal\Core\Messenger\Messenger
   */
  protected $messenger;

  /**
   * Renderer service
   *
   * @var Drupal\Core\Render\Renderer
   */
  protected $renderer;

  /**
   * Controller constructor
   *
   * @param Drupal\Core\Database\Connection $connection
   *   Database connection
   */
  public function __construct(Connection $connection, Messenger $messenger, Renderer $renderer) {
    $this->connection = $connection;
    $this->messenger = $messenger;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    $connection = $container->get('database');
    $messenger = $container->get('messenger');
    $renderer = $container->get('renderer');

    return new static($connection, $messenger, $renderer);
  }

  /**
   * Returns title for dashboard page
   *
   * @return string
   *   Title text
   */
  public function dashboardTitle() {
    return $this->t('Dashboard');
  }

  /**
   * Returns count of unique form submissions to a questionnaire
   *
   * @param  string $session_entity_uuid
   *   Session Entity UUID
   * @param  string $questionnaire_uuid
   *   Questionnaire UUID
   *
   * @return int
   *   Count of unique form submissions
   */
  private function getQuestionnaireSubmissionsCount(string $session_entity_uuid, string $questionnaire_uuid) {
    return (int)$this->connection->select('session_questionnaire_answer', 'sqa')
    ->condition('sqa.session_entity_uuid', $session_entity_uuid, '=')
    ->condition('sqa.questionnaire_uuid', $questionnaire_uuid, '=')
    ->groupBy('sqa.form_build_id')
    ->countQuery()
    ->execute()
    ->fetchField();
  }

  /**
   * Returns an array of counts with keys set as certain property value
   *
   * @param  string $key
   *   Property nake to use as key
   * @param  array  $counts
   *   Array of count objects
   *
   * @return array
   *   An array with $key => $count
   */
  private static function rekeyCountsBy(string $key, array $counts) {
    $data = [];

    if (sizeof($counts) > 0) {
      foreach ($counts as $count) {
        $data[$count->{$key}] = (int)$count->count;
      }
    }

    return $data;
  }

  /**
   * Returns counts of unique form submissions to all questionnaires that have
   * been answered at least once.
   *
   * @param  string $session_entity_uuid
   *   Session Entity UUID
   *
   * @return array
   *   An array of $questionnaire_uuid => $count
   */
  private function getAllQuestionnairesSubmissionsCounts(string $session_entity_uuid) {
    $query = $this->connection->select('session_questionnaire_answer', 'sqa')
    ->fields('sqa', ['questionnaire_uuid'])
    ->condition('sqa.session_entity_uuid', $session_entity_uuid, '=')
    ->groupBy('sqa.questionnaire_uuid');
    $query->addExpression('COUNT(DISTINCT sqa.form_build_id)', 'count');

    $counts = $query->execute()->fetchAll();

    return self::rekeyCountsBy('questionnaire_uuid', $counts);
  }

  /**
   * Returns counts for question options chosen
   *
   * @param  string $session_entity_uuid
   *   Session Entity UUID
   * @param  string $questionnaire_uuid
   *   Questionnaire UUID
   * @param  string $question_uuid
   *   Question UUID
   *
   * @return array
   *   Counts for options chosen by respondents
   */
  private function geQuestionAnswerCounts(string $session_entity_uuid, string $questionnaire_uuid, string $question_uuid) {
    $query = $this->connection->select('session_questionnaire_answer', 'sqa')
    ->fields('sqa', ['answer'])
    ->condition('sqa.session_entity_uuid', $session_entity_uuid, '=')
    ->condition('sqa.questionnaire_uuid', $questionnaire_uuid, '=')
    ->condition('sqa.question_uuid', $question_uuid, '=')
    ->groupBy('sqa.answer');
    $query->addExpression('COUNT(sqa.answer)', 'count');

    $counts = $query->execute()->fetchAll();

    return self::rekeyCountsBy('answer', $counts);
  }

  /**
   * Returns answer counts for all questions based on their presenc in the
   * database. Only elements that have any answers are present on both question
   * and questionnaire level.
   *
   * @param  string $session_entity_uuid
   *   Session Entity UUID
   * @param  array  $question_uuids
   *   Suitable question UUIDs
   *
   * @return array
   *   Multidimensional array with data $questionnaire_uuid => (array)$question_uuid => (array)$answer => $count
   */
  private function getAllQuestionsAnswerCounts(string $session_entity_uuid, array $question_uuids) {
    $data = [];

    if (sizeof($question_uuids) === 0) {
      return $data;
    }

    $query = $this->connection->select('session_questionnaire_answer', 'sqa')
    ->fields('sqa', ['questionnaire_uuid', 'question_uuid', 'answer'])
    ->condition('sqa.session_entity_uuid', $session_entity_uuid, '=')
    ->condition('sqa.question_uuid', $question_uuids, 'IN')
    ->groupBy('sqa.questionnaire_uuid, sqa.question_uuid, sqa.answer');
    $query->addExpression('COUNT(sqa.answer)', 'count');

    $counts = $query->execute()->fetchAll();

    if (sizeof($counts) > 0) {
      foreach ($counts as $count) {
        $data[$count->questionnaire_uuid][$count->question_uuid][$count->answer] = (int)$count->count;
      }
    }

    return $data;
  }

  /**
   * Extracts UUID identifiers for all the graphable questions
   *
   * @param  array  $questionnaires
   *   Array of questionnaire structural objects
   *
   * @return array
   *   UUID identifiers of suitable questions
   */
  private function extractGraphableQuestionUuids(array $questionnaires) {
    $uuids = [];

    foreach ($questionnaires as $questionnaire) {
      foreach ($questionnaire['questions'] as $question) {
        if ($this->isGraphableQuestionType($question['type'])) {
          $uuids[] = $question['uuid'];
        }
      }
    }

    return $uuids;
  }

  /**
   * Adds missing options with vaue of (also makes sure that original options
   * order is preserved)
   *
   * @param array $counts
   *   Answer counts data
   *
   * @param array $options
   *   All available options with counts
   */
  private function addMissingOptionsToCounts(array &$counts, array $options) {
    $tmp = [];

    array_walk($options, function($option, $key) use (&$tmp, &$counts) {
      $tmp[(string)$option] = (array_key_exists($option, $counts)) ? $counts[$option] : 0;
    });

    $counts = $tmp;
  }

  /**
   * Returns all answers for certain question
   *
   * @param  string $session_entity_uuid
   *   Session Entity UUID
   * @param  string $questionnaire_uuid
   *   Questionnaire UUID
   * @param  string $question_uuid
   *   Question UUID
   *
   * @return array
   *   Answers
   */
  private function getQuestionAnswers(string $session_entity_uuid, string $questionnaire_uuid, string $question_uuid) {
    $query = $this->connection->select('session_questionnaire_answer', 'sqa')
    ->fields('sqa', ['answer'])
    ->condition('sqa.session_entity_uuid', $session_entity_uuid, '=')
    ->condition('sqa.questionnaire_uuid', $questionnaire_uuid, '=')
    ->condition('sqa.question_uuid', $question_uuid, '=')
    ->groupBy('sqa.answer');

    return $query->execute()->fetchCol();
  }

  /**
   * Converts an array with answers into a structure suitable for table
   *
   * @param  array  $answers
   *   Answers
   *
   * @return array
   *   Answers where each row is an array
   */
  private function answersToTableRows(array &$answers) {
    $answers = array_map(function($answer) {
      return [$answer];
    }, $answers);
  }

  /**
   * Determines if provided question type is useble for showing graphs
   * @param  string  $type
   *   Question type
   * @return boolean
   */
  private function isGraphableQuestionType(string $type) {
    $type = SessionTemplate::processQuestionType($type);

    return in_array($type, ['multi-choice', 'checkboxes', 'scale']);
  }

  /**
   * Returns custom dashboard page structure (assumes one is set)
   * @param  Drupal\la_pills\Entity\SessionEntity $session_entity
   *   Session Entity
   * @param  array                                $structure
   *   Session template data structure
   * @return array
   *   Page structure
   */
  private function respondWithCustomDashboard(SessionEntity $session_entity, array $structure) {
    $downloadAnswersUrl = Url::fromRoute('session_entity.download_answers',[
      'session_entity' => $session_entity->id(),
    ], [
      'absolute' => TRUE,
      'query' =>
      [
        'token' => $session_entity->uuid(),
      ],
    ])->toString();
    $encoded = urlencode($downloadAnswersUrl);

    $src = str_replace('{{dataUrl}}', $encoded, $structure['dashboard']['url']);

    $response = [
      '#type' => 'html_tag',
      '#tag' => 'iframe',
      '#attributes' => [
        'src' => $src,
        'width' => isset($structure['dashboard']['width']) ? $structure['dashboard']['width'] : '100%',
        'height' => isset($structure['dashboard']['height']) ? $structure['dashboard']['height'] : '500px',
        'name' => 'external-dashboard',
      ],
    ];

    return $response;
  }

  /**
   * Returns dashboard page structure
   *
   * @return array
   *   Content structure
   */
  public function dashboard(SessionEntity $session_entity, Request $request) {
    $structure = $session_entity->getSessionTemplateData();

    // TODO See if we could use the hasExternalDashoard() metod of SessionTemplate class
    if (isset($structure['dashboard']) && isset($structure['dashboard']['url']) && $structure['dashboard']['url']) {
      $customDashoard = $this->respondWithCustomDashboard($session_entity, $structure);

      $showDefaltDashBoard = $request->get('show-default-dashboard') === 'true';

      if (!$showDefaltDashBoard) {
        return $customDashoard;
      } else {
        $response['iframe'] = $customDashoard;
      }
    }

    $response['dashboard'] = [
      '#attached' => [
        'library' => [
          'la_pills/session_entity_dashboard'
        ],
      ],
    ];

    $jsData = [];

    $submissions_counts = $this->getAllQuestionnairesSubmissionsCounts($session_entity->uuid());
    $graphable_uuids = $this->extractGraphableQuestionUuids($structure['questionnaires']);
    $all_graphable_counts = $this->getAllQuestionsAnswerCounts($session_entity->uuid(), $graphable_uuids);

    $questionnaire_index = 0;
    foreach ($structure['questionnaires'] as $questionnaire) {
      $questionnaire_index++;
      $response_count = (isset($submissions_counts[$questionnaire['uuid']])) ? $submissions_counts[$questionnaire['uuid']] : 0;

      $response[$questionnaire['uuid']] = [
        '#type' => 'container',
        '#attributes' => [
          'class' => ['questionnaire'],
          'id' => 'questionnaire-' . $questionnaire['uuid'],
        ],
      ];
      $response[$questionnaire['uuid']]['heading'] = [
        '#type' => 'html_tag',
        '#tag' => 'h2',
        '#value' => $questionnaire_index . '. ' . $questionnaire['title'] . (($response_count > 0) ? ' (' . $response_count . ')' : ''),
      ];

      $question_index = 0;
      foreach ($questionnaire['questions'] as $question) {
        $question_index++;
        $question_type = SessionTemplate::processQuestionType($question['type']);

        $response[$questionnaire['uuid']][$question['uuid']] = [
          '#type' => 'container',
          '#attributes' => [
            'class' => ['question'],
            'id' => 'question-' . $questionnaire['uuid'] . '-' . $question['uuid'],
          ],
        ];
        $response[$questionnaire['uuid']][$question['uuid']]['heading'] = [
          '#type' => 'html_tag',
          '#tag' => 'h3',
          '#value' => $questionnaire_index . '.' . $question_index . ' ' . $question['title'],
        ];

        if ($question_type === 'short-text' || $question_type === 'long-text') {
          $answers = $this->getQuestionAnswers($session_entity->uuid(), $questionnaire['uuid'], $question['uuid']);
          $this->answersToTableRows($answers);
          $has_answers = sizeof($answers) > 0;

          $response[$questionnaire['uuid']][$question['uuid']]['table'] = [
            '#type' => 'table',
            '#attributes' => [
              'class' => ['responses', $question_type, $has_answers ? 'has-answers' : 'no-answers'],
            ],
            '#header' => [$this->t('Responses') . ($has_answers ? ' (' . sizeof($answers) . ')' : '')],
            '#rows' => $answers,
          ];
        } else if ($this->isGraphableQuestionType($question_type)) {
          $options = ($question_type !== 'scale') ? $question['options'] : range($question['min'], $question['max'], 1);
          $counts = (isset($all_graphable_counts[$questionnaire['uuid']][$question['uuid']])) ? $all_graphable_counts[$questionnaire['uuid']][$question['uuid']] : [];
          $this->addMissingOptionsToCounts($counts, $options);

          $jsData[$questionnaire['uuid']][$question['uuid']] = [
            'id' =>'question-' . $questionnaire['uuid'] . '-' . $question['uuid'],
            'type' => $question_type,
            'options' => $options,
            'counts' => $counts,
            'title' => $question['title'],
          ];
        }
      }
    }

    $response['#attached']['drupalSettings']['laPillsSessionEntityDashboardData'] = $jsData;

    return $response;
  }

  /**
   * Returns title for questionnaire page
   *
   * @return string
   *   Title text
   */
  public function questionnaireTitle() {
    return $this->t('Questionnaire');
  }

  /**
   * Responds with JOSN data for questionnaire answer count
   *
   * @param Drupal\la_pills\Entity\SessionEntity $session_entity
   *   Session Entity object
   *
   * @return Symfony\Component\HttpFoundation\JsonResponse
   *   JSON response with data
   */
  public function restQuestionnaireCount(SessionEntity $session_entity) {
    if (!$session_entity->access('update')) {
      return new JsonResponse([], 403);
    }

    $query = $this->connection->select('session_questionnaire_answer', 'sqa');

    $query->condition('sqa.session_entity_uuid', $session_entity->uuid(), '=');
    $query->addField('sqa', 'questionnaire_uuid', 'uuid');
    $query->addExpression('COUNT(DISTINCT sqa.form_build_id)', 'count');
    $query->groupBy('sqa.questionnaire_uuid');

    $result = $query->execute();

    return new JsonResponse($result->fetchAll());
  }

  /**
   * Triggers file download with all the answers for a Session Entity
   *
   * @param Drupal\la_pills\Entity\SessionEntity $session_entity
   *   Session Entity object
   * @param Symfony\Component\HttpFoundation\Request       $request
   *   Request object
   *
   * @return Symfony\Component\HttpFoundation\Response
   *   Response object
   */
  public function downloadAnswers(SessionEntity $session_entity, Request $request) {
    // TODO Need to make sure that downloading answers has more protection
    // Permission check is the best way forward
    if ($session_entity->uuid() !== $request->get('token')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }
    /*if (!$session_entity->access('update')) {
      return new Response('', Response::HTTP_FORBIDDEN);
    }*/

    $random = new Random();

    $query = $this->connection->select('session_questionnaire_answer', 'sqa');

    $query->condition('sqa.session_entity_uuid', $session_entity->uuid(), '=');
    $query->fields('sqa', ['questionnaire_uuid', 'question_uuid', 'session_id', 'form_build_id', 'name', 'answer', 'created',]);
    $query->addExpression('FROM_UNIXTIME(created)', 'created');
    $query->leftJoin('users', 'u', 'sqa.user_id = u.uid');
    $query->addExpression('uuid', 'user_uuid');

    $result = $query->execute();

    $handle = fopen('php://temp', 'wb');

    fputcsv($handle, [
      $this->t('Session title'),
      $this->t('Questionnaire title'),
      $this->t('Question title'),
      $this->t('Question type'),
      $this->t('Session identifier'),
      $this->t('Form submission identifier'),
      $this->t('User identifier'),
      $this->t('Anonymous user name'),
      $this->t('Answer'),
      $this->t('Created'),
    ]);

    $template = $session_entity->getSessionTemplateData();
    $salt = $random->string();

    while ($row = $result->fetchObject()) {
      fputcsv($handle, [$session_entity->getName(), $template['questionnaires'][$row->questionnaire_uuid]['title'], $template['questions'][$row->question_uuid]['title'], $template['questions'][$row->question_uuid]['type'], hash('sha256', $row->session_id . $salt), hash('sha256', $row->form_build_id . $salt), $row->user_uuid, $row->name, $row->answer, $row->created,]);
    }
    rewind($handle);

    $response = new Response(stream_get_contents($handle));
    fclose($handle);

    $response->headers->set('Content-Type', 'text/csv');
    $response->headers->set('Content-Disposition','attachment; filename="answers.csv"');

    return $response;
  }

  /**
   * Callback for session closing ajax call
   *
   * @param  SessionEntity Drupal\la_pills\Entity\SessionEntity $session_entity
   *   Session Entity object
   * @return Drupal\Core\Ajax\AjaxResponse
   *   Ajax response with redirect command if successful
   */
  public function closeAjaxCallback(SessionEntity $session_entity) {
    $response = new AjaxResponse();

    if ($session_entity->isActive()) {
      $status_messages = [
        '#type' => 'status_messages',
      ];

      $session_entity->setActive(FALSE);
      $session_entity->save();

      $this->messenger->addMessage($this->t('Session has been closed. Answers are no longer accepted.'));

      $response->addCommand(new RemoveCommand('.button.close-session-button'));
      $response->addCommand(new AfterCommand('[data-drupal-messages-fallback]', $this->renderer->renderRoot($status_messages)));
    }

    return $response;
  }

}
