<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Countries;

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
                'station_id' => $station->getIdRequired(),
                'start' => $dateRange->getStartTimestamp(),
                'end' => $dateRange->getEndTimestamp(),
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
