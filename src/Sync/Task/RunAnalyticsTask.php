<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\SettingsAwareTrait;
use App\Entity\Analytics;
use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Enums\AnalyticsLevel;
use App\Entity\Repository\AnalyticsRepository;
use App\Entity\Repository\ListenerRepository;
use App\Entity\Repository\SongHistoryRepository;
use App\Entity\Station;
use Carbon\CarbonImmutable;

final class RunAnalyticsTask extends AbstractTask
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly AnalyticsRepository $analyticsRepo,
        private readonly ListenerRepository $listenerRepo,
        private readonly SongHistoryRepository $historyRepo,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '4 * * * *';
    }

    public static function isLongTask(): bool
    {
        return true;
    }

    public function run(bool $force = false): void
    {
        switch ($this->readSettings()->getAnalytics()) {
            case AnalyticsLevel::None:
                $this->purgeListeners();
                $this->purgeAnalytics();
                break;

            case AnalyticsLevel::NoIp:
                $this->purgeListeners();
                $this->updateAnalytics(false);
                break;

            case AnalyticsLevel::All:
                $this->updateAnalytics(true);
                break;
        }
    }

    private function updateAnalytics(bool $withListeners): void
    {
        $stationsRaw = $this->em->getRepository(Station::class)
            ->findAll();

        /** @var Station[] $stations */
        $stations = [];
        foreach ($stationsRaw as $station) {
            /** @var Station $station */
            $stations[$station->getId()] = $station;
        }

        $now = CarbonImmutable::now('UTC');
        $day = $now->subDays(5)->startOfDay();// Clear existing analytics in this segment

        $this->analyticsRepo->cleanup();

        while ($day < $now) {
            $this->em->wrapInTransaction(
                function () use ($day, $stations, $withListeners): void {
                    $this->processDay($day, $stations, $withListeners);
                }
            );

            $day = $day->addDay();
        }
    }

    /**
     * @param CarbonImmutable $day
     * @param Station[] $stations
     * @param bool $withListeners
     */
    private function processDay(
        CarbonImmutable $day,
        array $stations,
        bool $withListeners
    ): void {
        for ($hour = 0; $hour <= 23; $hour++) {
            $hourUtc = $day->setTime($hour, 0);

            $hourlyMin = null;
            $hourlyMax = null;
            $hourlyAverage = 0;
            $hourlyUniqueListeners = null;

            foreach ($stations as $station) {
                $stationTz = $station->getTimezoneObject();

                $start = $hourUtc->shiftTimezone($stationTz);
                $end = $start->addHour();

                [$min, $max, $avg] = $this->historyRepo->getStatsByTimeRange(
                    $station,
                    $start->getTimestamp(),
                    $end->getTimestamp()
                );

                $unique = null;
                if ($withListeners) {
                    $unique = $this->listenerRepo->getUniqueListeners($station, $start, $end);

                    $hourlyUniqueListeners ??= 0;
                    $hourlyUniqueListeners += $unique;
                }

                $this->analyticsRepo->clearSingleMetric(
                    AnalyticsIntervals::Hourly,
                    $hourUtc,
                    $station
                );

                $hourlyRow = new Analytics(
                    $hourUtc,
                    $station,
                    AnalyticsIntervals::Hourly,
                    $min,
                    $max,
                    $avg,
                    $unique
                );

                $this->em->persist($hourlyRow);

                if (null === $hourlyMin) {
                    $hourlyMin = $min;
                } else {
                    $hourlyMin = min($hourlyMin, $min);
                }

                if (null === $hourlyMax) {
                    $hourlyMax = $max;
                } else {
                    $hourlyMax = max($hourlyMax, $max);
                }

                $hourlyAverage += $avg;
            }

            // Post the all-stations hourly totals.
            $this->analyticsRepo->clearSingleMetric(
                AnalyticsIntervals::Hourly,
                $hourUtc
            );

            $hourlyAllStationsRow = new Analytics(
                $hourUtc,
                null,
                AnalyticsIntervals::Hourly,
                $hourlyMin ?? 0,
                $hourlyMax ?? 0,
                $hourlyAverage,
                $hourlyUniqueListeners
            );

            $this->em->persist($hourlyAllStationsRow);
        }

        // Aggregate daily totals.
        $dailyMin = null;
        $dailyMax = null;
        $dailyAverage = 0;
        $dailyUniqueListeners = null;

        foreach ($stations as $station) {
            $stationTz = $station->getTimezoneObject();
            $stationDayStart = $day->shiftTimezone($stationTz);
            $stationDayEnd = $stationDayStart->addDay();

            [$dailyStationMin, $dailyStationMax, $dailyStationAverage] = $this->historyRepo->getStatsByTimeRange(
                $station,
                $stationDayStart->getTimestamp(),
                $stationDayEnd->getTimestamp()
            );

            if (null === $dailyMin) {
                $dailyMin = $dailyStationMin;
            } else {
                $dailyMin = min($dailyMin, $dailyStationMin);
            }

            if (null === $dailyMax) {
                $dailyMax = $dailyStationMax;
            } else {
                $dailyMax = max($dailyMax, $dailyStationMax);
            }

            $dailyAverage += $dailyStationAverage;

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

            $this->analyticsRepo->clearSingleMetric(
                AnalyticsIntervals::Daily,
                $day,
                $station
            );

            $dailyStationRow = new Analytics(
                $day,
                $station,
                AnalyticsIntervals::Daily,
                $dailyStationMin,
                $dailyStationMax,
                $dailyStationAverage,
                $dailyStationUnique
            );

            $this->em->persist($dailyStationRow);
        }

        // Post the all-stations daily total.
        $this->analyticsRepo->clearSingleMetric(
            AnalyticsIntervals::Daily,
            $day
        );

        $dailyAllStationsRow = new Analytics(
            $day,
            null,
            AnalyticsIntervals::Daily,
            $dailyMin ?? 0,
            $dailyMax ?? 0,
            $dailyAverage,
            $dailyUniqueListeners
        );
        $this->em->persist($dailyAllStationsRow);
    }

    private function purgeAnalytics(): void
    {
        $this->analyticsRepo->clearAll();
    }

    private function purgeListeners(): void
    {
        $this->listenerRepo->clearAll();
    }
}
