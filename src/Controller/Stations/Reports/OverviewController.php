<?php

namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;

use function array_reverse;
use function array_slice;

class OverviewController
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Entity\Repository\AnalyticsRepository $analyticsRepo;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\AnalyticsRepository $analyticsRepo
    ) {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
        $this->analyticsRepo = $analyticsRepo;
    }

    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        // Get current analytics level.
        $analytics_level = $this->settingsRepo->getSetting(
            Entity\Settings::LISTENER_ANALYTICS,
            Entity\Analytics::LEVEL_ALL
        );

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            // The entirety of the dashboard can't be shown, so redirect user to the profile page.
            return $request->getView()->renderToResponse($response, 'stations/reports/restricted');
        }

        /* Statistics */
        $statisticsThreshold = CarbonImmutable::parse('-1 month', $station_tz);

        // Statistics by day.
        $dailyStats = $this->analyticsRepo->findForStationAfterTime(
            $station,
            $statisticsThreshold,
            Entity\Analytics::INTERVAL_DAILY
        );

        $daily_chart = new stdClass();
        $daily_chart->label = __('Listeners by Day');
        $daily_chart->type = 'line';
        $daily_chart->fill = false;

        $daily_alt = [
            '<p>' . $daily_chart->label . '</p>',
            '<dl>',
        ];
        $daily_averages = [];

        $days_of_week = [];

        foreach ($dailyStats as $stat) {
            /** @var CarbonImmutable $statTime */
            $statTime = $stat['moment'];
            $statTime = $statTime->shiftTimezone($station_tz);

            $avg_row = new stdClass();
            $avg_row->t = $statTime->getTimestamp() * 1000;
            $avg_row->y = round($stat['number_avg'], 2);
            $daily_averages[] = $avg_row;

            $row_date = $statTime->format('Y-m-d');
            $daily_alt[] = '<dt><time data-original="' . $avg_row->t . '">' . $row_date . '</time></dt>';
            $daily_alt[] = '<dd>' . $avg_row->y . ' ' . __('Listeners') . '</dd>';

            $day_of_week = (int)$statTime->format('N') - 1;
            $days_of_week[$day_of_week][] = $stat['number_avg'];
        }

        $daily_alt[] = '</dl>';
        $daily_chart->data = $daily_averages;

        $daily_data = [
            'datasets' => [$daily_chart],
        ];

        $day_of_week_chart = new stdClass();
        $day_of_week_chart->label = __('Listeners by Day of Week');

        $day_of_week_alt = [
            '<p>' . $day_of_week_chart->label . '</p>',
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

        foreach ($days_of_week_names as $day_index => $day_name) {
            $day_totals = $days_of_week[$day_index] ?? [0];

            $stat_value = round(array_sum($day_totals) / count($day_totals), 2);
            $day_of_week_stats[] = $stat_value;

            $day_of_week_alt[] = '<dt>' . $day_name . '</dt>';
            $day_of_week_alt[] = '<dd>' . $stat_value . ' ' . __('Listeners') . '</dd>';
        }

        $day_of_week_alt[] = '</dl>';
        $day_of_week_chart->data = $day_of_week_stats;

        $day_of_week_data = [
            'datasets' => [$day_of_week_chart],
            'labels' => $days_of_week_names,
        ];

        // Statistics by hour.
        $hourlyStats = $this->analyticsRepo->findForStationAfterTime(
            $station,
            $statisticsThreshold,
            Entity\Analytics::INTERVAL_HOURLY
        );

        $totals_by_hour = [];

        foreach ($hourlyStats as $stat) {
            /** @var CarbonImmutable $statTime */
            $statTime = $stat['moment'];
            $statTime = $statTime->shiftTimezone($station_tz);

            $hour = (int)$statTime->hour;
            $totals_by_hour[$hour][] = $stat['number_avg'];
        }

        $hourly_labels = [];
        $hourly_chart = new stdClass();
        $hourly_chart->label = __('Listeners by Hour');

        $hourly_rows = [];
        $hourly_alt = [
            '<p>' . $hourly_chart->label . '</p>',
            '<dl>',
        ];

        for ($i = 0; $i < 24; $i++) {
            $hourly_labels[] = $i . ':00';
            $totals = $totals_by_hour[$i] ?: [0];

            $stat_value = round(array_sum($totals) / count($totals), 2);
            $hourly_rows[] = $stat_value;

            $hourly_alt[] = '<dt>' . $i . ':00</dt>';
            $hourly_alt[] = '<dd>' . $stat_value . ' ' . __('Listeners') . '</dd>';
        }

        $hourly_alt[] = '</dl>';
        $hourly_chart->data = $hourly_rows;

        $hourly_data = [
            'datasets' => [$hourly_chart],
            'labels' => $hourly_labels,
        ];

        /* Play Count Statistics */

        $song_totals_raw = [];
        $song_totals_raw['played'] = $this->em->createQuery(/** @lang DQL */ 'SELECT
            sh.song_id, sh.text, sh.artist, sh.title, COUNT(sh.id) AS records
            FROM App\Entity\SongHistory sh
            WHERE sh.station_id = :station_id AND sh.timestamp_start >= :timestamp
            GROUP BY sh.song_id
            ORDER BY records DESC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('timestamp', $statisticsThreshold->getTimestamp())
            ->setMaxResults(40)
            ->getArrayResult();

        // Compile the above data.
        $song_totals = [];

        foreach ($song_totals_raw as $total_type => $total_records) {
            foreach ($total_records as $total_record) {
                $song_totals[$total_type][] = $total_record;
            }

            $song_totals[$total_type] = array_slice((array)$song_totals[$total_type], 0, 10, true);
        }

        /* Song "Deltas" (Changes in Listener Count) */
        $songPerformanceThreshold = CarbonImmutable::parse('-2 days', $station_tz)->getTimestamp();

        // Get all songs played in timeline.
        $songs_played_raw = $this->em->createQuery(/** @lang DQL */ 'SELECT sh
            FROM App\Entity\SongHistory sh
            WHERE sh.station_id = :station_id
            AND sh.timestamp_start >= :timestamp
            AND sh.listeners_start IS NOT NULL
            ORDER BY sh.timestamp_start ASC')
            ->setParameter('station_id', $station->getId())
            ->setParameter('timestamp', $songPerformanceThreshold)
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

            return $a <=> $b;
        });

        return $request->getView()->renderToResponse($response, 'stations/reports/overview', [
            'charts' => [
                'daily' => json_encode($daily_data, JSON_THROW_ON_ERROR),
                'daily_alt' => implode('', $daily_alt),
                'hourly' => json_encode($hourly_data, JSON_THROW_ON_ERROR),
                'hourly_alt' => implode('', $hourly_alt),
                'day_of_week' => json_encode($day_of_week_data, JSON_THROW_ON_ERROR),
                'day_of_week_alt' => implode('', $day_of_week_alt),
            ],
            'song_totals' => $song_totals,
            'best_performing_songs' => array_reverse(array_slice($songs, -5)),
            'worst_performing_songs' => array_slice($songs, 0, 5),
        ]);
    }
}
