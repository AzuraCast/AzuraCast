<?php
namespace Controller\Stations;

use Entity\Song;

class IndexController extends BaseController
{
    public function indexAction()
    {
        /**
         * Statistics
         */

        $threshold = strtotime('-1 month');

        // Statistics by day.

        /** @var \InfluxDB\Database $influx */
        $influx = $this->di[\InfluxDB\Database::class];

        $resultset = $influx->query('SELECT * FROM "1d"."station.' . $this->station->getId() . '.listeners" WHERE time > now() - 30d',
            [
                'epoch' => 'ms',
            ]);

        $daily_stats = $resultset->getPoints();

        $daily_ranges = [];
        $daily_averages = [];
        $days_of_week = [];

        foreach ($daily_stats as $stat) {
            // Add 12 hours to statistics so they always land inside the day they represent.
            $stat['time'] = $stat['time'] + (60 * 60 * 12 * 1000);

            $daily_ranges[] = [$stat['time'], $stat['min'], $stat['max']];
            $daily_averages[] = [$stat['time'], round($stat['value'], 2)];

            $day_of_week = date('l', round($stat['time'] / 1000));
            $days_of_week[$day_of_week][] = $stat['value'];
        }

        $day_of_week_stats = [];
        foreach ($days_of_week as $day_name => $day_totals) {
            $day_of_week_stats[] = [$day_name, round(array_sum($day_totals) / count($day_totals), 2)];
        }

        $this->view->day_of_week_stats = json_encode($day_of_week_stats);

        $this->view->daily_ranges = json_encode($daily_ranges);
        $this->view->daily_averages = json_encode($daily_averages);

        // Statistics by hour.
        $resultset = $influx->query('SELECT * FROM "1h"."station.' . $this->station->getId() . '.listeners"', [
            'epoch' => 'ms',
        ]);

        $hourly_stats = $resultset->getPoints();

        $hourly_averages = [];
        $hourly_ranges = [];
        $totals_by_hour = [];

        foreach ($hourly_stats as $stat) {
            $hourly_ranges[] = [$stat['time'], $stat['min'], $stat['max']];
            $hourly_averages[] = [$stat['time'], round($stat['value'], 2)];

            $hour = date('G', round($stat['time'] / 1000));
            $totals_by_hour[$hour][] = $stat['value'];
        }

        $this->view->hourly_ranges = json_encode($hourly_ranges);
        $this->view->hourly_averages = json_encode($hourly_averages);

        $averages_by_hour = [];
        for ($i = 0; $i < 24; $i++) {
            $totals = $totals_by_hour[$i] ?: [0];
            $averages_by_hour[] = [$i . ':00', round(array_sum($totals) / count($totals), 2)];
        }

        $this->view->averages_by_hour = json_encode($averages_by_hour);

        /**
         * Play Count Statistics
         */

        $song_totals_raw = [];
        $song_totals_raw['played'] = $this->em->createQuery('SELECT sh.song_id, COUNT(sh.id) AS records
            FROM Entity\SongHistory sh
            WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp
            GROUP BY sh.song_id
            ORDER BY records DESC')
            ->setParameter('station_id', $this->station->getId())
            ->setParameter('timestamp', $threshold)
            ->setMaxResults(40)
            ->getArrayResult();

        $ignored_songs = $this->_getIgnoredSongs();
        $song_totals_raw['played'] = array_filter($song_totals_raw['played'], function ($value) use ($ignored_songs) {
            return !(isset($ignored_songs[$value['song_id']]));
        });

        // Compile the above data.
        $song_totals = [];
        foreach ($song_totals_raw as $total_type => $total_records) {
            foreach ($total_records as $total_record) {
                $song = $this->em->getRepository(Song::class)->findAsArray($total_record['song_id']);
                $total_record['song'] = $song;

                $song_totals[$total_type][] = $total_record;
            }

            $song_totals[$total_type] = array_slice((array)$song_totals[$total_type], 0, 10, true);
        }

        $this->view->song_totals = $song_totals;

        /**
         * Song "Deltas" (Changes in Listener Count)
         */

        $songs_played_raw = $this->_getEligibleHistory();
        $songs = [];

        foreach ($songs_played_raw as $i => $song_row) {
            // Song has no recorded ending.
            if ($song_row['timestamp_end'] == 0) {
                continue;
            }

            $song_row['stat_start'] = $song_row['listeners_start'];
            $song_row['stat_end'] = $song_row['listeners_end'];
            $song_row['stat_delta'] = $song_row['delta_total'];

            $songs[] = $song_row;
        }

        usort($songs, function ($a_arr, $b_arr) {
            $a = $a_arr['stat_delta'];
            $b = $b_arr['stat_delta'];

            if ($a == $b) {
                return 0;
            }

            return ($a > $b) ? 1 : -1;
        });

        $this->view->best_performing_songs = array_reverse(array_slice((array)$songs, -5));
        $this->view->worst_performing_songs = array_slice((array)$songs, 0, 5);
    }


}