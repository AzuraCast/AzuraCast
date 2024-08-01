<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Entity\Repository\StationHlsStreamRepository;
use App\Entity\Repository\StationMountRepository;
use App\Entity\Repository\StationRemoteRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ByStream extends AbstractReportAction
{
    public function __construct(
        private readonly StationMountRepository $mountRepo,
        private readonly StationRemoteRepository $remoteRepo,
        private readonly StationHlsStreamRepository $hlsStreamRepo
    ) {
    }

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
                SELECT l.stream_id, 
                       COUNT(l.listener_hash) AS listeners, 
                       SUM(l.connected_seconds) AS connected_seconds
                FROM (
                    SELECT CASE
                        WHEN mount_id IS NOT NULL THEN CONCAT('local_', mount_id)
                        WHEN hls_stream_id IS NOT NULL THEN CONCAT('hls_', hls_stream_id)
                        WHEN remote_id IS NOT NULL THEN CONCAT('remote_', remote_id)
                        ELSE 'unknown'
                    END AS stream_id,
                        SUM(timestamp_end - timestamp_start) AS connected_seconds,
                        listener_hash
                    FROM listener
                    WHERE station_id = :station_id 
                    AND timestamp_end >= :start
                    AND timestamp_start <= :end
                    GROUP BY listener_hash
                ) AS l
                GROUP BY l.stream_id
            SQL,
            [
                'station_id' => $station->getIdRequired(),
                'start' => $dateRange->getStartTimestamp(),
                'end' => $dateRange->getEndTimestamp(),
            ]
        );

        $streamLookup = [];
        foreach ($this->mountRepo->getDisplayNames($station) as $id => $displayName) {
            $streamLookup['local_' . $id] = $displayName;
        }
        foreach ($this->remoteRepo->getDisplayNames($station) as $id => $displayName) {
            $streamLookup['remote_' . $id] = $displayName;
        }
        foreach ($this->hlsStreamRepo->getDisplayNames($station) as $id => $displayName) {
            $streamLookup['hls_' . $id] = $displayName;
        }

        $listenersByStream = [];
        $connectedTimeByStream = [];
        $stats = [];

        foreach ($statsRaw as $row) {
            if (!isset($streamLookup[$row['stream_id']])) {
                continue;
            }

            $row['stream'] = $streamLookup[$row['stream_id']];
            $stats[] = $row;

            $listenersByStream[$row['stream']] = $row['listeners'];
            $connectedTimeByStream[$row['stream']] = $row['connected_seconds'];
        }

        return $response->withJson([
            'all' => $stats,
            'top_listeners' => $this->buildChart($listenersByStream, __('Listeners')),
            'top_connected_time' => $this->buildChart($connectedTimeByStream, __('Connected Seconds')),
        ]);
    }
}
