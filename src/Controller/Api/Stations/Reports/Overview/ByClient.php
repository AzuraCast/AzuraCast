<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ByClient extends AbstractReportAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        // Get current analytics level.
        if (!$this->isAllAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $dateRange = $this->getDateRange($request, $stationTz);

        $statsRaw = $this->em->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT l.client_raw, 
                       COUNT(l.listener_hash) AS listeners, 
                       SUM(l.connected_seconds) AS connected_seconds
                FROM (
                    SELECT
                    CASE
                        WHEN device_is_bot = 1 THEN 'bot'
                        WHEN device_is_mobile = 1 THEN 'mobile'
                        WHEN device_is_browser = 1 THEN 'desktop'
                        ELSE 'non_browser'
                    END AS client_raw,
                        SUM(timestamp_end - timestamp_start) AS connected_seconds,
                        listener_hash
                    FROM listener
                    WHERE station_id = :station_id 
                    AND timestamp_end >= :start
                    AND timestamp_start <= :end
                    GROUP BY listener_hash
                ) AS l
                GROUP BY l.client_raw
            SQL,
            [
                'station_id' => $station->getIdRequired(),
                'start' => $dateRange->getStartTimestamp(),
                'end' => $dateRange->getEndTimestamp(),
            ]
        );

        $clientTypes = [
            'bot' => __('Bot/Crawler'),
            'mobile' => __('Mobile Device'),
            'desktop' => __('Desktop Browser'),
            'non_browser' => __('Non-Browser'),
        ];

        $listenersByClient = [];
        $connectedTimeByClient = [];
        $stats = [];

        foreach ($statsRaw as $row) {
            $row['client'] = $clientTypes[$row['client_raw']];
            $stats[] = $row;

            $listenersByClient[$row['client']] = $row['listeners'];
            $connectedTimeByClient[$row['client']] = $row['connected_seconds'];
        }

        return $response->withJson([
            'all' => $stats,
            'top_listeners' => $this->buildChart($listenersByClient, __('Listeners'), null),
            'top_connected_time' => $this->buildChart($connectedTimeByClient, __('Connected Seconds'), null),
        ]);
    }
}
