<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Sync\Task\RunAutomatedAssignmentTask;
use App\Utilities\Csv;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class PerformanceController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected RunAutomatedAssignmentTask $sync_automation
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $format = 'html'
    ): ResponseInterface {
        $station = $request->getStation();

        $automation_config = (array)$station->getAutomationSettings();
        $threshold_days = (int)($automation_config['threshold_days']
            ?? RunAutomatedAssignmentTask::DEFAULT_THRESHOLD_DAYS);

        $report_data = $this->sync_automation->generateReport($station, $threshold_days);

        // Do not show songs that are not in playlists.
        $report_data = array_filter(
            $report_data,
            static function ($media) {
                return !(empty($media['playlists']));
            }
        );

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

        if ($format === 'json') {
            return $response->withJson($report_data);
        }

        return $request->getView()->renderToResponse($response, 'stations/reports/performance', [
            'report_data' => $report_data,
        ]);
    }
}
