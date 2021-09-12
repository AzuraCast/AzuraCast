<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator;
use App\Sync\Task\RunAutomatedAssignmentTask;
use App\Utilities\Csv;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class PerformanceAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        RunAutomatedAssignmentTask $automationTask
    ): ResponseInterface {
        $station = $request->getStation();

        $automation_config = (array)$station->getAutomationSettings();
        $threshold_days = (int)($automation_config['threshold_days']
            ?? RunAutomatedAssignmentTask::DEFAULT_THRESHOLD_DAYS);

        $report_data = $automationTask->generateReport($station, $threshold_days);

        // Do not show songs that are not in playlists.
        $report_data = array_filter(
            $report_data,
            static function ($media) {
                return !(empty($media['playlists']));
            }
        );

        $params = $request->getQueryParams();
        $format = $params['format'] ?? 'json';

        if ($format === 'csv') {
            $export_csv = [
                [
                    'Song Title',
                    'Song Artist',
                    'Filename',
                    'Length',
                    'Current Playlist',
                    'Delta Joins',
                    'Delta Losses',
                    'Delta Total',
                    'Play Count',
                    'Play Percentage',
                    'Weighted Ratio',
                ],
            ];

            foreach ($report_data as $row) {
                $export_csv[] = [
                    $row['title'],
                    $row['artist'],
                    $row['path'],
                    $row['length'],

                    implode('/', $row['playlists']),
                    $row['delta_positive'],
                    $row['delta_negative'],
                    $row['delta_total'],

                    $row['num_plays'],
                    $row['percent_plays'] . '%',
                    $row['ratio'],
                ];
            }

            $csv_file = Csv::arrayToCsv($export_csv);
            $csv_filename = $station->getShortName() . '_media_' . date('Ymd') . '.csv';

            return $response->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
        }

        $paginator = Paginator::fromArray($report_data, $request);
        return $paginator->write($response);
    }
}
