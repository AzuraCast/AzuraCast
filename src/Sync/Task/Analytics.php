<?php
namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use InfluxDB\Database;
use Psr\Log\LoggerInterface;

class Analytics extends AbstractTask
{
    protected Database $influx;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Database $influx
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->influx = $influx;
    }

    public function run(bool $force = false): void
    {
        $analytics_level = $this->settingsRepo->getSetting('analytics', Entity\Analytics::LEVEL_ALL);

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            $this->purgeAnalytics();
            $this->purgeListeners();
        } elseif ($analytics_level === Entity\Analytics::LEVEL_NO_IP) {
            $this->purgeListeners();
        } else {
            $this->clearOldAnalytics();
        }
    }

    protected function purgeAnalytics(): void
    {
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Analytics a')
            ->execute();

        $this->influx->query('DROP SERIES FROM /.*/');
    }

    protected function purgeListeners(): void
    {
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Listener l')
            ->execute();
    }

    protected function clearOldAnalytics(): void
    {
        // Clear out any non-daily statistics.
        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Analytics a WHERE a.type != :type')
            ->setParameter('type', 'day')
            ->execute();

        // Pull statistics in from influx.
        $resultset = $this->influx->query('SELECT * FROM "1d"./.*/ WHERE time > now() - 14d', [
            'epoch' => 's',
        ]);

        $results_raw = $resultset->getSeries();
        $results = [];
        foreach ($results_raw as $serie) {
            $points = [];
            foreach ($serie['values'] as $point) {
                $points[] = array_combine($serie['columns'], $point);
            }

            $results[$serie['name']] = $points;
        }

        $new_records = [];
        $earliest_timestamp = time();

        foreach ($results as $stat_series => $stat_rows) {
            $series_split = explode('.', $stat_series);
            $station_id = ($series_split[1] === 'all') ? null : $series_split[1];

            foreach ($stat_rows as $stat_row) {
                if ($stat_row['time'] < $earliest_timestamp) {
                    $earliest_timestamp = $stat_row['time'];
                }

                $new_records[] = [
                    'station_id' => $station_id,
                    'type' => 'day',
                    'timestamp' => $stat_row['time'],
                    'number_min' => (int)$stat_row['min'],
                    'number_max' => (int)$stat_row['max'],
                    'number_avg' => round($stat_row['value']),
                ];
            }
        }

        $this->em->createQuery(/** @lang DQL */ 'DELETE FROM App\Entity\Analytics a WHERE a.timestamp >= :earliest')
            ->setParameter('earliest', $earliest_timestamp)
            ->execute();

        $all_stations = $this->em->getRepository(Entity\Station::class)->findAll();
        $stations_by_id = [];
        foreach ($all_stations as $station) {
            $stations_by_id[$station->getId()] = $station;
        }

        foreach ($new_records as $row) {
            if (empty($row['station_id']) || isset($stations_by_id[$row['station_id']])) {
                $record = new Entity\Analytics(
                    $row['station_id'] ? $stations_by_id[$row['station_id']] : null,
                    $row['type'],
                    $row['timestamp'],
                    $row['number_min'],
                    $row['number_max'],
                    $row['number_avg']
                );
                $this->em->persist($record);
            }
        }

        $this->em->flush();
    }
}
