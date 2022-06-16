<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use stdClass;

final class ChartsAction extends AbstractReportAction
{
    public function __construct(
        Entity\Repository\SettingsRepository $settingsRepo,
        EntityManagerInterface $em,
        private readonly Entity\Repository\AnalyticsRepository $analyticsRepo,
    ) {
        parent::__construct($settingsRepo, $em);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        // Get current analytics level.
        if (!$this->isAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Entity\Api\Status(false, 'Reporting is restricted due to system analytics level.'));
        }

        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $dateRange = $this->getDateRange($request, $stationTz);

        $stats = [];

        // Statistics by day.
        $dailyStats = $this->analyticsRepo->findForStationInRange(
            $station,
            $dateRange
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
            $statTime = $statTime->shiftTimezone($stationTz);

            $avg_row = new stdClass();
            $avg_row->x = $statTime->getTimestampMs();
            $avg_row->y = round((float)$stat['number_avg'], 2);
            $daily_averages[] = $avg_row;

            $row_date = $statTime->format('Y-m-d');
            $daily_alt[] = '<dt><time data-original="' . $avg_row->x . '">' . $row_date . '</time></dt>';
            $daily_alt[] = '<dd>' . $avg_row->y . ' ' . __('Listeners') . '</dd>';

            $day_of_week = (int)$statTime->format('N') - 1;
            $days_of_week[$day_of_week][] = $stat['number_avg'];
        }

        $daily_alt[] = '</dl>';
        $daily_chart->data = $daily_averages;

        $stats['daily'] = [
            'metrics' => [
                $daily_chart,
            ],
            'alt' => implode('', $daily_alt),
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

        $stats['day_of_week'] = [
            'labels' => $days_of_week_names,
            'metrics' => [
                $day_of_week_chart,
            ],
            'alt' => implode('', $day_of_week_alt),
        ];

        // Statistics by hour.
        $hourlyStats = $this->analyticsRepo->findForStationInRange(
            $station,
            $dateRange,
            Entity\Analytics::INTERVAL_HOURLY
        );

        $totals_by_hour = [];

        foreach ($hourlyStats as $stat) {
            /** @var CarbonImmutable $statTime */
            $statTime = $stat['moment'];
            $statTime = $statTime->shiftTimezone($stationTz);

            $hour = $statTime->hour;
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
            $totals = $totals_by_hour[$i] ?? [0];

            $stat_value = round(array_sum($totals) / count($totals), 2);
            $hourly_rows[] = $stat_value;

            $hourly_alt[] = '<dt>' . $i . ':00</dt>';
            $hourly_alt[] = '<dd>' . $stat_value . ' ' . __('Listeners') . '</dd>';
        }

        $hourly_alt[] = '</dl>';
        $hourly_chart->data = $hourly_rows;

        $stats['hourly'] = [
            'labels' => $hourly_labels,
            'metrics' => [
                $hourly_chart,
            ],
            'alt' => implode('', $hourly_alt),
        ];

        return $response->withJson($stats);
    }
}
