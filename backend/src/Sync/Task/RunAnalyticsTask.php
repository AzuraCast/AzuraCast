<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\SettingsAwareTrait;
use App\Doctrine\Platform\MariaDbPlatform;
use App\Entity\Analytics;
use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Enums\AnalyticsLevel;
use App\Entity\Repository\AnalyticsRepository;
use App\Entity\Repository\ListenerRepository;
use App\Entity\Repository\SongHistoryRepository;
use App\Entity\Station;
use App\Utilities\File;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use League\Csv\Writer;
use Symfony\Component\Filesystem\Filesystem;
use Throwable;

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
        switch ($this->readSettings()->analytics ?? AnalyticsLevel::default()) {
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
        $this->analyticsRepo->cleanup();

        // Write all new analytics as a single giant CSV.
        $tempCsvPath = File::generateTempPath('mariadb_analytics.csv');
        new Filesystem()->chmod($tempCsvPath, 0o777);

        $csv = Writer::createFromPath($tempCsvPath);
        $csv->setEscape('');
        $csv->addFormatter(function ($row) {
            return array_map(function ($col) {
                if (null === $col) {
                    return '\N';
                }

                return is_string($col)
                    ? str_replace('"', '""', $col)
                    : $col;
            }, $row);
        });

        $now = Time::nowUtc();
        $day = $now->subDays(3)->startOfDay();// Clear existing analytics in this segment

        while ($day < $now) {
            try {
                $this->processDay($day, $withListeners, $csv);
            } catch (Throwable $e) {
                $this->logger->error(
                    sprintf(
                        'Error processing analytics for day "%s": %s',
                        $day->toDateString(),
                        $e->getMessage()
                    ),
                    [
                        'exception' => $e,
                    ]
                );
            }

            $this->em->clear();
            $day = $day->addDay();
        }

        // Use LOAD DATA INFILE for bulk analytics dumps.
        $tableName = $this->em->getClassMetadata(Analytics::class)->getTableName();
        $conn = $this->em->getConnection();

        $csvLoadQuery = sprintf(
            <<<'SQL'
                LOAD DATA LOCAL INFILE %s REPLACE
                INTO TABLE %s 
                FIELDS TERMINATED BY ','
                OPTIONALLY ENCLOSED BY '"'
                LINES TERMINATED BY '\n'
                (%s)
            SQL,
            $conn->quote($tempCsvPath),
            $conn->quoteSingleIdentifier($tableName),
            implode(
                ',',
                array_map(
                    fn($col) => $conn->quoteSingleIdentifier($col),
                    [
                        'moment',
                        'station_id',
                        'type',
                        'number_min',
                        'number_max',
                        'number_avg',
                        'number_unique',
                    ]
                )
            )
        );

        try {
            $conn->executeQuery($csvLoadQuery);
        } finally {
            @unlink($tempCsvPath);
        }
    }

    private function processDay(
        CarbonImmutable $day,
        bool $withListeners,
        Writer $csv,
    ): void {
        $stations = $this->em->getRepository(Station::class)->findAll();

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
                    $start,
                    $end
                );

                $unique = null;
                if ($withListeners) {
                    $unique = $this->listenerRepo->getUniqueListeners($station, $start, $end);

                    $hourlyUniqueListeners ??= 0;
                    $hourlyUniqueListeners += $unique;
                }

                $csv->insertOne([
                    Time::toUtcCarbonImmutable($hourUtc)
                        ->format(MariaDbPlatform::DB_DATETIME_FORMAT),
                    $station->id,
                    AnalyticsIntervals::Hourly->value,
                    $min,
                    $max,
                    $avg,
                    $unique,
                ]);

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
            $csv->insertOne([
                Time::toUtcCarbonImmutable($hourUtc)
                    ->format(MariaDbPlatform::DB_DATETIME_FORMAT),
                null,
                AnalyticsIntervals::Hourly->value,
                $hourlyMin ?? 0,
                $hourlyMax ?? 0,
                $hourlyAverage,
                $hourlyUniqueListeners,
            ]);
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
                $stationDayStart,
                $stationDayEnd
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

            $csv->insertOne([
                Time::toUtcCarbonImmutable($day)
                    ->format(MariaDbPlatform::DB_DATETIME_FORMAT),
                $station->id,
                AnalyticsIntervals::Daily->value,
                $dailyStationMin,
                $dailyStationMax,
                $dailyStationAverage,
                $dailyStationUnique,
            ]);
        }

        // Post the all-stations daily total.
        $csv->insertOne([
            Time::toUtcCarbonImmutable($day)
                ->format(MariaDbPlatform::DB_DATETIME_FORMAT),
            null,
            AnalyticsIntervals::Daily->value,
            $dailyMin ?? 0,
            $dailyMax ?? 0,
            $dailyAverage,
            $dailyUniqueListeners,
        ]);
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
