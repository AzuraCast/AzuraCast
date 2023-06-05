<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Carbon\CarbonImmutable;
use Psr\Log\LoggerInterface;

final class RunAnalyticsTask extends AbstractTask
{
    public function __construct(
        private readonly Entity\Repository\SettingsRepository $settingsRepo,
        private readonly Entity\Repository\AnalyticsRepository $analyticsRepo,
        private readonly Entity\Repository\ListenerRepository $listenerRepo,
        private readonly Entity\Repository\SongHistoryRepository $historyRepo,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
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
        switch ($this->settingsRepo->readSettings()->getAnalytics()) {
            case Entity\Enums\AnalyticsLevel::None:
                $this->purgeListeners();
                $this->purgeAnalytics();
                break;

            case Entity\Enums\AnalyticsLevel::NoIp:
                $this->purgeListeners();
                $this->updateAnalytics(false);
                break;

            case Entity\Enums\AnalyticsLevel::All:
                $this->updateAnalytics(true);
                break;
        }
    }

    private function updateAnalytics(bool $withListeners): void
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
     * @param Entity\Station[] $stations
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
                    Entity\Enums\AnalyticsIntervals::Hourly,
                    $hourUtc,
                    $station
                );

                $hourlyRow = new Entity\Analytics(
                    $hourUtc,
                    $station,
                    Entity\Enums\AnalyticsIntervals::Hourly,
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
                Entity\Enums\AnalyticsIntervals::Hourly,
                $hourUtc
            );

            $hourlyAllStationsRow = new Entity\Analytics(
                $hourUtc,
                null,
                Entity\Enums\AnalyticsIntervals::Hourly,
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
                Entity\Enums\AnalyticsIntervals::Daily,
                $day,
                $station
            );

            $dailyStationRow = new Entity\Analytics(
                $day,
                $station,
                Entity\Enums\AnalyticsIntervals::Daily,
                $dailyStationMin,
                $dailyStationMax,
                $dailyStationAverage,
                $dailyStationUnique
            );

            $this->em->persist($dailyStationRow);
        }

        // Post the all-stations daily total.
        $this->analyticsRepo->clearSingleMetric(
            Entity\Enums\AnalyticsIntervals::Daily,
            $day
        );

        $dailyAllStationsRow = new Entity\Analytics(
            $day,
            null,
            Entity\Enums\AnalyticsIntervals::Daily,
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
