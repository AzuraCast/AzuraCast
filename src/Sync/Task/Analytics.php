<?php

namespace App\Sync\Task;

use App\Entity;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class Analytics extends AbstractTask
{
    protected Entity\Repository\AnalyticsRepository $analyticsRepo;

    protected Entity\Repository\ListenerRepository $listenerRepo;

    protected Entity\Repository\SongHistoryRepository $historyRepo;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\AnalyticsRepository $analyticsRepo,
        Entity\Repository\ListenerRepository $listenerRepo,
        Entity\Repository\SongHistoryRepository $historyRepo
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->analyticsRepo = $analyticsRepo;
        $this->listenerRepo = $listenerRepo;
        $this->historyRepo = $historyRepo;
    }

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
        $stationsRaw = $this->em->getRepository(Entity\Station::class)
            ->findAll();

        /** @var Entity\Station[] $stations */
        $stations = [];
        foreach ($stationsRaw as $station) {
            /** @var Entity\Station $station */
            $stations[$station->getId()] = $station;
        }

        $now = CarbonImmutable::now('UTC');
        $day = $now->subDays(5)->setTime(0, 0);// Clear existing analytics in this segment

        $this->analyticsRepo->cleanup();
        $this->analyticsRepo->clearAllAfterTime($day);

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
                    $end = $start->addHour();

                    [$min, $max, $avg] = $this->historyRepo->getStatsByTimeRange($station, $start, $end);

                    $unique = null;
                    if ($withListeners) {
                        $unique = $this->listenerRepo->getUniqueListeners($station, $start, $end);

                        $hourlyUniqueListeners ??= 0;
                        $hourlyUniqueListeners += $unique;
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
            $dailyAverage = 0;
            $dailyUniqueListeners = null;

            foreach ($stations as $stationId => $station) {
                $stationTz = $station->getTimezoneObject();
                $stationDayStart = $day->shiftTimezone($stationTz);

                $stationDayEnd = $stationDayStart->addDay();

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

                $dailyStationUnique = null;
                if ($withListeners) {
                    $dailyStationUnique = $this->listenerRepo->getUniqueListeners(
                        $station,
                        $stationDayStart,
                        $stationDayEnd
                    );

                    $dailyUniqueListeners ??= 0;
                    $dailyUniqueListeners += $dailyStationUnique;
                }

                $dailyStationAverage = round(array_sum($dailyStationAverages) / count($dailyStationAverages), 2);
                $dailyAverage += $dailyStationAverage;

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
        $this->analyticsRepo->clearAll();
    }

    protected function purgeListeners(): void
    {
        $this->listenerRepo->clearAll();
    }
}
