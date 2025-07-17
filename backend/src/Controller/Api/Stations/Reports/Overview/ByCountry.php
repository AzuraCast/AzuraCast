<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Countries;

#[OA\Get(
    path: '/station/{station_id}/reports/overview/by-country',
    operationId: 'getStationReportByCountry',
    summary: 'Get the "Listeners by Country" report for a station.',
    tags: [OpenApi::TAG_STATIONS_REPORTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        // TODO API Response
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ByCountry extends AbstractReportAction
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
                SELECT l.location_country AS country_code, 
                       COUNT(l.listener_hash) AS listeners,
                       SUM(l.connected_seconds) AS connected_seconds
                FROM (
                    SELECT location_country, SUM(timestamp_end - timestamp_start) AS connected_seconds, listener_hash
                    FROM listener
                    WHERE station_id = :station_id 
                    AND location_country IS NOT NULL
                    AND timestamp_end >= :start
                    AND timestamp_start <= :end
                    GROUP BY listener_hash
                ) AS l
                GROUP BY l.location_country
            SQL,
            [
                'station_id' => $station->id,
                'start' => $dateRange->start,
                'end' => $dateRange->end,
            ]
        );

        $countryNames = Countries::getNames($request->getLocale()->getLocaleWithoutEncoding());

        $listenersByCountry = [];
        $connectedTimeByCountry = [];
        $stats = [];

        foreach ($statsRaw as $stat) {
            if (empty($stat['country_code'])) {
                continue;
            }

            $stat['country'] = $countryNames[$stat['country_code']];
            $stats[] = $stat;

            $listenersByCountry[$stat['country']] = $stat['listeners'];
            $connectedTimeByCountry[$stat['country']] = $stat['connected_seconds'];
        }

        return $response->withJson([
            'all' => $stats,
            'top_listeners' => $this->buildChart($listenersByCountry, __('Listeners')),
            'top_connected_time' => $this->buildChart($connectedTimeByCountry, __('Connected Seconds')),
        ]);
    }
}
