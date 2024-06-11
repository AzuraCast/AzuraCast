<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ByBrowser extends AbstractReportAction
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

        $stats = $this->em->getConnection()->fetchAllAssociative(
            <<<'SQL'
                SELECT device_browser_family AS browser, 
                       COUNT(l.listener_hash) AS listeners, 
                       SUM(l.connected_seconds) AS connected_seconds
                FROM (
                    SELECT device_browser_family, 
                           SUM(timestamp_end - timestamp_start) AS connected_seconds, 
                           listener_hash
                    FROM listener
                    WHERE station_id = :station_id 
                    AND device_browser_family IS NOT NULL
                    AND timestamp_end >= :start
                    AND timestamp_start <= :end
                    AND device_is_browser = 1
                    GROUP BY listener_hash
                ) AS l
                GROUP BY l.device_browser_family
            SQL,
            [
                'station_id' => $station->getIdRequired(),
                'start' => $dateRange->getStartTimestamp(),
                'end' => $dateRange->getEndTimestamp(),
            ]
        );

        $listenersByBrowser = array_column($stats, 'listeners', 'browser');
        $connectedTimeByBrowser = array_column($stats, 'connected_seconds', 'browser');

        return $response->withJson([
            'all' => $stats,
            'top_listeners' => $this->buildChart($listenersByBrowser, __('Listeners')),
            'top_connected_time' => $this->buildChart($connectedTimeByBrowser, __('Connected Seconds')),
        ]);
    }
}
