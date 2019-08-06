<?php
namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\RequestHelper;
use Doctrine\ORM\EntityManager;
use InfluxDB\Database;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class OverviewController
{
    /** @var EntityManager */
    protected $em;

    /** @var Database */
    protected $influx;

    /**
     * @param EntityManager $em
     * @param Database $influx
     */
    public function __construct(EntityManager $em, Database $influx)
    {
        $this->em = $em;
        $this->influx = $influx;
    }

    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);

        // Get current analytics level.

        /** @var Entity\Repository\SettingsRepository $settings_repo */
        $settings_repo = $this->em->getRepository(Entity\Settings::class);

        $analytics_level = $settings_repo->getSetting(Entity\Settings::LISTENER_ANALYTICS, Entity\Analytics::LEVEL_ALL);

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            // The entirety of the dashboard can't be shown, so redirect user to the profile page.
            return RequestHelper::getView($request)->renderToResponse($response, 'stations/reports/restricted');
        }

        /* Statistics */
        $threshold = strtotime('-1 month');

        // Statistics by day.
        $resultset = $this->influx->query('SELECT * FROM "1d"."station.' . $station->getId() . '.listeners" WHERE time > now() - 30d', [
            'epoch' => 'ms',
        ]);

        $daily_chart = new \stdClass;
        $daily_chart->label = __('Listeners by Day');
        $daily_chart->type = 'line';
        $daily_chart->fill = false;

        $daily_alt = [
            '<p>'.$daily_chart->label.'</p>',
            '<dl>',
        ];
        $daily_averages = [];

        $days_of_week = [];

        foreach ($resultset->getPoints() as $stat) {
            $avg_row = new \stdClass;
            $avg_row->t = $stat['time'];
            $avg_row->y = round($stat['value'], 2);
            $daily_averages[] = $avg_row;

            $row_date = gmdate('Y-m-d', $avg_row->t/1000);
            $daily_alt[] = '<dt><time data-original="'.$avg_row->t.'">'.$row_date.'</time></dt>';
            $daily_alt[] = '<dd>'.$avg_row->y.' '.__('Listeners').'</dd>';

            $day_of_week = (int)gmdate('N', round($stat['time'] / 1000)) - 1;
            $days_of_week[$day_of_week][] = $stat['value'];
        }

        $daily_alt[] = '</dl>';
        $daily_chart->data = $daily_averages;

        $daily_data = [
            'datasets' => [$daily_chart],
        ];

        $day_of_week_chart = new \stdClass;
        $day_of_week_chart->label = __('Listeners by Day of Week');

        $day_of_week_alt = [
            '<p>'.$day_of_week_chart->label.'</p>',
            '<dl>',
        ];

        $days_of_week_names = [
            __('Monday'),
            __('Tuesday'),
            __('Wednesday'),
            __('Thursday'),
            __('Friday'),
            __('Saturday'),
            __('Sunday'),
        ];

        $day_of_week_stats = [];

        foreach($days_of_week_names as $day_index => $day_name) {
            $day_totals = $days_of_week[$day_index] ?? [0];

            $stat_value = round(array_sum($day_totals) / count($day_totals), 2);
            $day_of_week_stats[] = $stat_value;

            $day_of_week_alt[] = '<dt>'.$day_name.'</dt>';
            $day_of_week_alt[] = '<dd>'.$stat_value.' '.__('Listeners').'</dd>';
        }

        $day_of_week_alt[] = '</dl>';
        $day_of_week_chart->data = $day_of_week_stats;

        $day_of_week_data = [
            'datasets' => [$day_of_week_chart],
            'labels' => $days_of_week_names,
        ];

        // Statistics by hour.
        $resultset = $this->influx->query('SELECT * FROM "1h"."station.' . $station->getId() . '.listeners"', [
            'epoch' => 'ms',
        ]);

        $hourly_stats = $resultset->getPoints();

        $hourly_averages = [];
        $hourly_ranges = [];
        $totals_by_hour = [];

        foreach ($hourly_stats as $stat) {
            $hourly_ranges[] = [$stat['time'], $stat['min'], $stat['max']];
            $hourly_averages[] = [$stat['time'], round($stat['value'], 2)];

            $hour = (int)gmdate('G', round($stat['time'] / 1000));
            $totals_by_hour[$hour][] = $stat['value'];
        }

        $hourly_labels = [];
        $hourly_chart = new \stdClass;
        $hourly_chart->label = __('Listeners by Hour');

        $hourly_rows = [];
        $hourly_alt = [
            '<p>'.$hourly_chart->label.'</p>',
            '<dl>',
        ];

        for ($i = 0; $i < 24; $i++) {
            $hourly_labels[] = $i.':00';
            $totals = $totals_by_hour[$i] ?: [0];

            $stat_value = round(array_sum($totals) / count($totals), 2);
            $hourly_rows[] = $stat_value;

            $hourly_alt[] = '<dt>'.$i.':00</dt>';
            $hourly_alt[] = '<dd>'.$stat_value.' '.__('Listeners').'</dd>';
        }

        $hourly_alt[] = '</dl>';
        $hourly_chart->data = $hourly_rows;

        $hourly_data = [
            'datasets' => [$hourly_chart],
            'labels' => $hourly_labels,
        ];

        /* Play Count Statistics */

        $song_totals_raw = [];
        $song_totals_raw['played'] = $this->em->createQuery(/** @lang DQL */'SELECT 
            sh.song_id, COUNT(sh.id) AS records
            FROM App\Entity\SongHistory sh
            WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp
            GROUP BY sh.song_id
            ORDER BY records DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('timestamp', $threshold)
            ->setMaxResults(40)
            ->getArrayResult();

        // Compile the above data.
        $song_totals = [];

        /** @var Entity\Repository\SongRepository $song_repo */
        $song_repo = $this->em->getRepository(Entity\Song::class);

        $get_song_q = $this->em->createQuery(/** @lang DQL */'SELECT s 
            FROM App\Entity\Song s
            WHERE s.id = :song_id');

        foreach ($song_totals_raw as $total_type => $total_records) {
            foreach ($total_records as $total_record) {
                $song = $get_song_q->setParameter('song_id', $total_record['song_id'])
                    ->getArrayResult();

                $total_record['song'] = $song[0];

                $song_totals[$total_type][] = $total_record;
            }

            $song_totals[$total_type] = array_slice((array)$song_totals[$total_type], 0, 10, true);
        }

        /* Song "Deltas" (Changes in Listener Count) */
        $threshold = strtotime('-2 weeks');

        // Get all songs played in timeline.
        $songs_played_raw = $this->em->createQuery(/** @lang DQL */'SELECT sh, s
            FROM App\Entity\SongHistory sh
            LEFT JOIN sh.song s
            WHERE sh.station_id = :station_id 
            AND sh.timestamp_start >= :timestamp 
            AND sh.listeners_start IS NOT NULL
            ORDER BY sh.timestamp_start ASC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('timestamp', $threshold)
            ->getArrayResult();

        $songs_played_raw = array_values($songs_played_raw);
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

        return RequestHelper::getView($request)->renderToResponse($response, 'stations/reports/overview', [
            'charts' => [
                'daily'         => json_encode($daily_data),
                'daily_alt'     => implode('', $daily_alt),
                'hourly'        => json_encode($hourly_data),
                'hourly_alt'    => implode('', $hourly_alt),
                'day_of_week'   => json_encode($day_of_week_data),
                'day_of_week_alt' => implode('', $day_of_week_alt),
            ],
            'song_totals' => $song_totals,
            'best_performing_songs' => \array_reverse(\array_slice($songs, -5)),
            'worst_performing_songs' => \array_slice($songs, 0, 5),
        ]);
    }
}
