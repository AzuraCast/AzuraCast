<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Entity\Api\Status;
use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Repository\AnalyticsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Psr\Http\Message\ResponseInterface;
use stdClass;

final class ChartsAction extends AbstractReportAction
{
    public function __construct(
        private readonly AnalyticsRepository $analyticsRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        // Get current analytics level.
        if (!$this->isAnalyticsEnabled()) {
            return $response->withStatus(400)
                ->withJson(new Status(false, 'Reporting is restricted due to system analytics level.'));
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

        $dailyChart = new stdClass();
        $dailyChart->label = __('Listeners by Day');
        $dailyChart->type = 'line';
        $dailyChart->fill = false;

        $dailyAlt = [
            'label' => $dailyChart->label,
            'values' => [],
        ];

        $dailyAverages = [];

        $daysOfWeek = [];

        foreach ($dailyStats as $stat) {
            /** @var CarbonImmutable $statTime */
            $statTime = $stat['moment'];
            $statTime = $statTime->shiftTimezone($stationTz);

            $avgRow = new stdClass();
            $avgRow->x = $statTime->getTimestampMs();
            $avgRow->y = round((float)$stat['number_avg'], 2);
            $dailyAverages[] = $avgRow;

            $rowDate = $statTime->format('Y-m-d');

            $dailyAlt['values'][] = [
                'label' => $rowDate,
                'type' => 'time',
                'original' => $avgRow->x,
                'value' => $avgRow->y . ' ' . __('Listeners'),
            ];

            $dayOfWeek = (int)$statTime->format('N') - 1;
            $daysOfWeek[$dayOfWeek][] = $stat['number_avg'];
        }

        $dailyChart->data = $dailyAverages;

        $stats['daily'] = [
            'metrics' => [
                $dailyChart,
            ],
            'alt' => [
                $dailyAlt,
            ],
        ];

        $dayOfWeekChart = new stdClass();
        $dayOfWeekChart->label = __('Listeners by Day of Week');

        $dayOfWeekAlt = [
            'label' => $dayOfWeekChart->label,
            'values' => [],
        ];

        $daysOfWeekNames = [
            __('Monday'),
            __('Tuesday'),
            __('Wednesday'),
            __('Thursday'),
            __('Friday'),
            __('Saturday'),
            __('Sunday'),
        ];

        $dayOfWeekStats = [];

        foreach ($daysOfWeekNames as $dayIndex => $dayName) {
            $dayTotals = $daysOfWeek[$dayIndex] ?? [0];

            $statValue = round(array_sum($dayTotals) / count($dayTotals), 2);
            $dayOfWeekStats[] = $statValue;

            $dayOfWeekAlt['values'][] = [
                'label' => $dayName,
                'type' => 'string',
                'value' => $statValue . ' ' . __('Listeners'),
            ];
        }

        $dayOfWeekChart->data = $dayOfWeekStats;

        $stats['day_of_week'] = [
            'labels' => $daysOfWeekNames,
            'metrics' => [
                $dayOfWeekChart,
            ],
            'alt' => [
                $dayOfWeekAlt,
            ],
        ];

        // Statistics by hour.
        $hourlyStats = $this->analyticsRepo->findForStationInRange(
            $station,
            $dateRange,
            AnalyticsIntervals::Hourly
        );

        $totalsByHour = [];

        foreach ($hourlyStats as $stat) {
            /** @var CarbonImmutable $statTime */
            $statTime = $stat['moment'];
            $statTime = $statTime->shiftTimezone($stationTz);

            $hour = $statTime->hour;
            $totalsByHour[$hour][] = $stat['number_avg'];
        }

        $hourlyLabels = [];
        $hourlyChart = new stdClass();
        $hourlyChart->label = __('Listeners by Hour');

        $hourlyRows = [];

        $hourlyAlt = [
            'label' => $hourlyChart->label,
            'values' => [],
        ];

        for ($i = 0; $i < 24; $i++) {
            $hourlyLabels[] = $i . ':00';
            $totals = $totalsByHour[$i] ?? [0];

            $statValue = round(array_sum($totals) / count($totals), 2);
            $hourlyRows[] = $statValue;

            $hourlyAlt['values'][] = [
                'label' => $i . ':00',
                'type' => 'string',
                'value' => $statValue . ' ' . __('Listeners'),
            ];
        }

        $hourlyChart->data = $hourlyRows;

        $stats['hourly'] = [
            'labels' => $hourlyLabels,
            'metrics' => [
                $hourlyChart,
            ],
            'alt' => [
                $hourlyAlt,
            ],
        ];

        return $response->withJson($stats);
    }
}
