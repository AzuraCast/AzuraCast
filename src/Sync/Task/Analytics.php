<?php
namespace App\Sync\Task;

use App\Entity;
use Carbon\CarbonImmutable;

class Analytics extends AbstractTask
{
    public function run(bool $force = false): void
    {
        $analytics_level = $this->settingsRepo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);

        switch ($analytics_level) {
            case Entity\Analytics::LEVEL_NONE:
                $this->purgeListeners();
                $this->purgeAnalytics();
                break;

            case Entity\Analytics::LEVEL_NO_IP:
                $this->purgeListeners();
                $this->updateAnalytics(false);
                break;

            case Entity\Analytics::LEVEL_ALL:
                $this->updateAnalytics(true);
                break;
        }
    }

    protected function updateAnalytics(bool $withListeners = true): void
    {
        $historyTotalsQuery = $this->em->createQuery(/** @lang DQL */ '
            SELECT AVG(sh.listeners_end) AS listeners_avg, MAX(sh.listeners_end) AS listeners_max, MIN(sh.listeners_end) AS listeners_min
            FROM App\Entity\SongHistory sh
            WHERE sh.station = :station
            AND sh.timestamp_end >= :start
            AND sh.timestamp_start <= :end');

        $uniqueListenersQuery = $this->em->createQuery(/** @lang DQL */ '
            SELECT COUNT(DISTINCT l.listener_hash) AS unique_listeners
            FROM App\Entity\Listener l
            WHERE l.station = :station
            AND l.timestamp_end >= :start
            AND l.timestamp_start <= :end');

        $stationsRaw = $this->em->getRepository(Entity\Station::class)
            ->findAll();

        /** @var Entity\Station[] $stations */
        $stations = [];
        foreach ($stationsRaw as $station) {
            /** @var Entity\Station $station */
            $stations[$station->getId()] = $station;
        }

        $now = CarbonImmutable::now('UTC');
        $day = $now->subDays(5)->setTime(0, 0);

        // Clear existing analytics in this segment
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Analytics a WHERE a.moment >= :threshold')
            ->setParameter('threshold', $day)
            ->execute();

        while ($day < $now) {
            $dailyUniqueListeners = null;

            for ($hour = 0; $hour <= 23; $hour++) {
                $hourUtc = $day->setTime($hour, 0);

                $hourlyMin = 0;
                $hourlyMax = 0;
                $hourlyAverage = 0;
                $hourlyUniqueListeners = null;
                $hourlyStationRows = [];

                foreach ($stations as $stationId => $station) {
                    $stationTz = $station->getTimezoneObject();

                    $start = $hourUtc->shiftTimezone($stationTz);
                    $startTimestamp = $start->getTimestamp();

                    $end = $start->addHour();
                    $endTimestamp = $end->getTimestamp();

                    $historyTotals = $historyTotalsQuery
                        ->setParameter('station', $station)
                        ->setParameter('start', $startTimestamp)
                        ->setParameter('end', $endTimestamp)
                        ->getSingleResult();

                    $min = (int)$historyTotals['listeners_min'];
                    $max = (int)$historyTotals['listeners_max'];
                    $avg = round($historyTotals['listeners_avg'], 2);

                    if ($withListeners) {
                        $unique = (int)$uniqueListenersQuery
                            ->setParameter('station', $station)
                            ->setParameter('start', $startTimestamp)
                            ->setParameter('end', $endTimestamp)
                            ->getSingleScalarResult();

                        $hourlyUniqueListeners ??= 0;
                        $hourlyUniqueListeners += $unique;
                    } else {
                        $unique = null;
                    }

                    $hourlyRow = new Entity\Analytics(
                        $hourUtc,
                        $station,
                        Entity\Analytics::INTERVAL_HOURLY,
                        $min,
                        $max,
                        $avg,
                        $unique
                    );
                    $hourlyStationRows[$stationId][] = $hourlyRow;

                    $this->em->persist($hourlyRow);

                    $hourlyMin = min($hourlyMin, $min);
                    $hourlyMax = max($hourlyMax, $max);
                    $hourlyAverage += $avg;
                }

                // Post the all-stations hourly totals.
                $hourlyAllStationsRow = new Entity\Analytics(
                    $hourUtc,
                    null,
                    Entity\Analytics::INTERVAL_HOURLY,
                    $hourlyMin,
                    $hourlyMax,
                    $hourlyAverage,
                    $hourlyUniqueListeners
                );
                $hourlyStationRows['all'][] = $hourlyAllStationsRow;

                $this->em->persist($hourlyAllStationsRow);
            }

            // Aggregate daily totals.
            $dailyMin = 0;
            $dailyMax = 0;
            $dailyAverages = [];
            $dailyUniqueListeners = null;

            foreach ($stations as $stationId => $station) {
                $stationTz = $station->getTimezoneObject();
                $stationDayStart = $day->shiftTimezone($stationTz);
                $stationDayStartTimestamp = $stationDayStart->getTimestamp();

                $stationDayEnd = $stationDayStart->addDay();
                $stationDayEndTimestamp = $stationDayEnd->getTimestamp();

                $dailyStationMin = 0;
                $dailyStationMax = 0;
                $dailyStationAverages = [];

                $hourlyRows = $hourlyStationRows[$stationId] ?? [];
                foreach ($hourlyRows as $hourlyRow) {
                    /** @var Entity\Analytics $hourlyRow */

                    $dailyStationMin = min($dailyStationMin, $hourlyRow->getNumberMin());
                    $dailyStationMax = max($dailyStationMax, $hourlyRow->getNumberMax());
                    $dailyStationAverages[] = $hourlyRow->getNumberAvg();
                }

                $dailyMin = min($dailyMin, $dailyStationMin);
                $dailyMax = max($dailyMax, $dailyStationMax);

                if ($withListeners) {
                    $dailyStationUnique = (int)$uniqueListenersQuery
                        ->setParameter('station', $station)
                        ->setParameter('start', $stationDayStartTimestamp)
                        ->setParameter('end', $stationDayEndTimestamp)
                        ->getSingleScalarResult();

                    $dailyUniqueListeners ??= 0;
                    $dailyUniqueListeners += $dailyStationUnique;
                } else {
                    $dailyStationUnique = null;
                }

                $dailyStationAverage = round(array_sum($dailyStationAverages) / count($dailyStationAverages), 2);
                $dailyAverages[] = $dailyStationAverage;

                $dailyStationRow = new Entity\Analytics(
                    $day,
                    $station,
                    Entity\Analytics::INTERVAL_DAILY,
                    $dailyStationMin,
                    $dailyStationMax,
                    $dailyStationAverage,
                    $dailyStationUnique
                );

                $this->em->persist($dailyStationRow);
            }

            // Post the all-stations daily total.
            $dailyAverage = round(array_sum($dailyAverages) / count($dailyAverages), 2);

            $dailyAllStationsRow = new Entity\Analytics(
                $day,
                null,
                Entity\Analytics::INTERVAL_DAILY,
                $dailyMin,
                $dailyMax,
                $dailyAverage,
                $dailyUniqueListeners
            );
            $this->em->persist($dailyAllStationsRow);

            $this->em->flush();

            // Loop to the next day.
            $day = $day->addDay();
        }
    }

    protected function purgeAnalytics(): void
    {
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Analytics a')
            ->execute();
    }

    protected function purgeListeners(): void
    {
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Listener l')
            ->execute();
    }
}
