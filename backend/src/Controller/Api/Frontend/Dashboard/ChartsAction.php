<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Error;
use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Station;
use App\Enums\GlobalPermissions;
use App\Enums\StationPermissions;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Carbon\CarbonImmutable;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

#[
    OA\Put(
        path: '/frontend/dashboard/charts',
        operationId: 'getDashboardCharts',
        summary: 'Get the measurements for the dashboard charts.',
        tags: [OpenApi::TAG_MISC],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class ChartsAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly CacheInterface $cache,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        if (!$this->readSettings()->isAnalyticsEnabled()) {
            return $response->withStatus(403, 'Forbidden')
                ->withJson(new Error(403, 'Analytics are disabled for this installation.'));
        }

        $acl = $request->getAcl();

        // Don't show stations the user can't manage.
        $showAdmin = $acl->isAllowed(GlobalPermissions::View);

        /** @var Station[] $stations */
        $stations = array_filter(
            $this->em->getRepository(Station::class)->findAll(),
            static function ($station) use ($acl) {
                /** @var Station $station */
                return $station->is_enabled &&
                    $acl->isAllowed(StationPermissions::View, $station->id);
            }
        );

        // Generate unique cache ID for stations.
        $stationIds = [];
        foreach ($stations as $station) {
            $stationId = $station->id;
            $stationIds[$stationId] = $stationId;
        }
        $cacheName = 'homepage_metrics_' . implode(',', $stationIds);

        if ($this->cache->has($cacheName)) {
            $stationStats = $this->cache->get($cacheName);
        } else {
            $threshold = CarbonImmutable::parse('-180 days');

            $stats = $this->em->createQuery(
                <<<'DQL'
                    SELECT IDENTITY(a.station) AS station_id, a.moment, a.number_avg, a.number_unique
                    FROM App\Entity\Analytics a
                    WHERE IDENTITY(a.station) IN (:stations)
                    AND a.type = :type
                    AND a.moment >= :threshold
                DQL
            )->setParameter('stations', $stationIds)
                ->setParameter('type', AnalyticsIntervals::Daily)
                ->setParameter('threshold', $threshold)
                ->getArrayResult();

            $showAllStations = $showAdmin && count($stationIds) > 1;

            $rawStats = [
                'average' => [],
                'unique' => [],
            ];

            foreach ($stats as $row) {
                $stationId = $row['station_id'];
                $moment = $row['moment'];

                $sortableKey = $moment->format('Y-m-d');
                $jsTimestamp = $moment->getTimestamp() * 1000;

                $average = round((float)$row['number_avg'], 2);
                $unique = $row['number_unique'];

                $rawStats['average'][$stationId][$sortableKey] = [
                    $jsTimestamp,
                    $average,
                ];

                if (null !== $unique) {
                    $rawStats['unique'][$stationId][$sortableKey] = [
                        $jsTimestamp,
                        $unique,
                    ];
                }

                if ($showAllStations) {
                    if (!isset($rawStats['average']['all'][$sortableKey])) {
                        $rawStats['average']['all'][$sortableKey] = [
                            $jsTimestamp,
                            0,
                        ];
                    }
                    $rawStats['average']['all'][$sortableKey][1] += $average;

                    if (null !== $unique) {
                        if (!isset($stationStats['unique']['all'][$sortableKey])) {
                            $stationStats['unique']['all'][$sortableKey] = [
                                $jsTimestamp,
                                0,
                            ];
                        }
                        $stationStats['unique']['all'][$sortableKey][1] += $unique;
                    }
                }
            }

            $stationsInMetric = [];
            if ($showAllStations) {
                $stationsInMetric['all'] = __('All Stations');
            }

            foreach ($stations as $station) {
                $stationsInMetric[$station->id] = $station->name;
            }

            $stationStats = [
                'average' => [
                    'metrics' => [],
                    'alt' => [],
                ],
                'unique' => [
                    'metrics' => [],
                    'alt' => [],
                ],
            ];

            foreach ($stationsInMetric as $stationId => $stationName) {
                foreach ($rawStats as $statKey => $statRows) {
                    if (!isset($statRows[$stationId])) {
                        continue;
                    }

                    $series = [
                        'label' => $stationName,
                        'type' => 'line',
                        'fill' => false,
                        'data' => [],
                    ];

                    $stationAlt = [
                        'label' => $stationName,
                        'values' => [],
                    ];

                    ksort($statRows[$stationId]);

                    foreach ($statRows[$stationId] as $sortableKey => [$jsTimestamp, $value]) {
                        $series['data'][] = [
                            'x' => $jsTimestamp,
                            'y' => $value,
                        ];

                        $stationAlt['values'][] = [
                            'label' => $sortableKey,
                            'type' => 'time',
                            'original' => $jsTimestamp,
                            'value' => $value . ' ' . __('Listeners'),
                        ];
                    }

                    $stationStats[$statKey]['alt'][] = $stationAlt;
                    $stationStats[$statKey]['metrics'][] = $series;
                }
            }

            $this->cache->set($cacheName, $stationStats, 600);
        }

        return $response->withJson($stationStats);
    }
}
