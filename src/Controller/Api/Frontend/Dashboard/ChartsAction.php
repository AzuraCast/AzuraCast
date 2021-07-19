<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Dashboard;

use App\Acl;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class ChartsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        CacheInterface $cache,
        Entity\Repository\SettingsRepository $settingsRepo
    ): ResponseInterface {
        $analyticsLevel = $settingsRepo->readSettings()->getAnalytics();
        if ($analyticsLevel === Entity\Analytics::LEVEL_NONE) {
            return $response->withStatus(403, 'Forbidden')
                ->withJson(new Entity\Api\Error(403, 'Analytics are disabled for this installation.'));
        }

        $acl = $request->getAcl();

        // Don't show stations the user can't manage.
        $showAdmin = $acl->isAllowed(Acl::GLOBAL_VIEW);

        /** @var Entity\Station[] $stations */
        $stations = array_filter(
            $em->getRepository(Entity\Station::class)->findAll(),
            static function ($station) use ($acl) {
                /** @var Entity\Station $station */
                return $station->isEnabled() &&
                    $acl->isAllowed(Acl::STATION_VIEW, $station->getId());
            }
        );

        // Generate unique cache ID for stations.
        $stationIds = [];
        foreach ($stations as $station) {
            $stationId = $station->getId();
            $stationIds[$stationId] = $stationId;
        }
        $cacheName = 'homepage_metrics_' . implode(',', $stationIds);

        if ($cache->has($cacheName)) {
            $stationStats = $cache->get($cacheName);
        } else {
            $threshold = CarbonImmutable::parse('-180 days');

            $stats = $em->createQuery(
                <<<'DQL'
                    SELECT a.station_id, a.moment, a.number_avg, a.number_unique
                    FROM App\Entity\Analytics a
                    WHERE a.station_id IN (:stations)
                    AND a.type = :type
                    AND a.moment >= :threshold
                DQL
            )->setParameter('stations', $stationIds)
                ->setParameter('type', Entity\Analytics::INTERVAL_DAILY)
                ->setParameter('threshold', $threshold)
                ->getArrayResult();

            $showAllStations = $showAdmin && count($stationIds) > 1;

            $rawStats = [
                'average' => [],
                'unique' => [],
            ];

            foreach ($stats as $row) {
                $stationId = $row['station_id'];

                /** @var CarbonImmutable $moment */
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
                $stationsInMetric[$station->getId()] = $station->getName();
            }

            $stationStats = [
                'average' => [
                    'metrics' => [],
                    'alt' => '',
                ],
                'unique' => [
                    'metrics' => [],
                    'alt' => '',
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

                    $stationStats[$statKey]['alt'] .= '<p>' . $stationName . '</p>';
                    $stationStats[$statKey]['alt'] .= '<dl>';

                    ksort($statRows[$stationId]);

                    foreach ($statRows[$stationId] as $sortableKey => [$jsTimestamp, $value]) {
                        $series['data'][] = [
                            't' => $jsTimestamp,
                            'y' => $value,
                        ];

                        $stationStats[$statKey]['alt'] .= sprintf(
                            '<dt><time data-original="%s">%s</time></dt>',
                            $jsTimestamp,
                            $sortableKey
                        );
                        $stationStats[$statKey]['alt'] .= '<dd>' . $value . ' ' . __('Listeners') . '</dd>';
                    }

                    $stationStats[$statKey]['alt'] .= '</dl>';
                    $stationStats[$statKey]['metrics'][] = $series;
                }
            }

            $cache->set($cacheName, $stationStats, 600);
        }

        return $response->withJson($stationStats);
    }
}
