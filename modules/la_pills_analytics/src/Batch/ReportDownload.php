<?php

namespace Drupal\la_pills_analytics\Batch;

use Drupal\Core\Database\Query\SelectInterface;
use Symfony\Component\HttpFoundation\Response;

class ReportDownload {

    /**
     * Preprocess data, add base query and create temporary csv file
     *
     * @param SelectInterface $query   Base query with all conditions already applied
     * @param array           $context Shared context data
     * 
     * @return void
     */
    public static function preprocess(SelectInterface $query, array &$context) : void {
        $fileSystem = \Drupal::service('file_system');

        $context['results']['base_query'] = $query;
        // TODO Might need to handle file creation issues
        $context['results']['file_path'] = $fileSystem->tempnam('temporary://', 'report_');

        $handle = fopen($context['results']['file_path'], 'wb');

        fputcsv($handle, [
            t('Type'),
            t('Path'),
            t('URI'),
            t('Title'),
            t('Session'),
            t('User'),
            t('Name'),  
            t('Created'),
        ]);

        fclose($handle);
    }

    /**
     * Process data and write csv to the file
     *
     * @param array $data    Range data with start and length keys
     * @param array $context Shared context data
     * 
     * @return void
     */
    public static function process(array $data, array &$context) : void {
        $query = clone $context['results']['base_query'];
        $query->range($data['start'], $data['length']);
        $query->orderBy('a.created', 'ASC');

        $result = $query->execute();

        $dateFormatter = \Drupal::service('date.formatter');

        $handle = fopen($context['results']['file_path'], 'ab');

        foreach ($result->fetchAll() as $action) {
            fputcsv($handle, [
                $action->type,
                $action->path,
                $action->uri,
                $action->title,
                $action->session_id,
                $action->user_id ? $action->user_id : '',
                $action->user_id ? '' : $action->name,
                $dateFormatter->format($action->created, 'long'),
            ]);
        }

        fclose($handle);
    }

    /**
     * Deals with final data processing and stores file path into the session
     *
     * @param boolean $success    Successful or unsuccessful processing
     * @param array   $results    Results that are set on shared context
     * @param array   $operations 
     * 
     * @return void
     */
    public static function finished(bool $success, array $results, array $operations) : void {
        $messenger = \Drupal::service('messenger');

        if ($success) {
            $messenger->addStatus(t('Report creation successful. Download should start shortly.'));
            
            $request = \Drupal::request();
            $session = $request->getSession();
            $session->set('la_pills_analytics_report_path', $results['file_path']);
        }

        if (!$success) {
            $messenger->addError(t('Could not create a report.'));
        }
    }

}