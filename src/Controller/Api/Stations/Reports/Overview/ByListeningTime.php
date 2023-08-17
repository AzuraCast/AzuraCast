<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ByListeningTime extends AbstractReportAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        // Get current analytics level.
        if (!$this->isAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $dateRange = $this->getDateRange($request, $stationTz);

        $statsRaw = $this->em->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT SUM(timestamp_end - timestamp_start) AS connected_seconds, listener_hash
                FROM listener
                WHERE station_id = :station_id 
                AND timestamp_end >= :start
                AND timestamp_start <= :end
                GROUP BY listener_hash
            SQL,
            [
                'station_id' => $station->getIdRequired(),
                'start' => $dateRange->getStartTimestamp(),
                'end' => $dateRange->getEndTimestamp(),
            ]
        );

        $ranges = [
            [30, __('Less than Thirty Seconds')],
            [60, __('Thirty Seconds to One Minute')],
            [300, __('One Minute to Five Minutes')],
            [600, __('Five Minutes to Ten Minutes')],
            [1800, __('Ten Minutes to Thirty Minutes')],
            [3600, __('Thirty Minutes to One Hour')],
            [7200, __('One Hour to Two Hours')],
            [PHP_INT_MAX, __('More than Two Hours')],
        ];

        $statsByRange = [];
        foreach ($ranges as [$max, $label]) {
            $statsByRange[$label] = 0;
        }

        foreach ($statsRaw as $row) {
            $listenerTime = (int)$row['connected_seconds'];

            foreach ($ranges as [$max, $label]) {
                if ($listenerTime <= $max) {
                    $statsByRange[$label]++;
                    break;
                }
            }
        }

        $stats = [];
        foreach ($statsByRange as $key => $row) {
            $stats[] = [
                'label' => $key,
                'value' => $row,
            ];
        }

        return $response->withJson([
            'all' => $stats,
            'chart' => $this->buildChart(
                array_filter($statsByRange),
                __('Listeners'),
                null
            ),
        ]);
    }
}
