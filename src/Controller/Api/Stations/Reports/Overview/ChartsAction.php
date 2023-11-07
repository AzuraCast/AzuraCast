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

        $queryParams = $request->getQueryParams();
        $statKey = match ($queryParams['type'] ?? null) {
            'average' => 'number_avg',
            default => 'number_unique'
        };

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
            $avgRow->y = round((float)$stat[$statKey], 2);
            $dailyAverages[] = $avgRow;

            $rowDate = $statTime->format('Y-m-d');

            $dailyAlt['values'][] = [
                'label' => $rowDate,
                'type' => 'time',
                'original' => $avgRow->x,
                'value' => $avgRow->y . ' ' . __('Listeners'),
            ];

            $dayOfWeek = (int)$statTime->format('N') - 1;
            $daysOfWeek[$dayOfWeek][] = $stat[$statKey];
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

            $statValue = ($statKey === 'number_unique')
                ? array_sum($dayTotals)
                : round(array_sum($dayTotals) / count($dayTotals), 2);

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

        $hourlyTotalCategories = [
            'all',
            'day0',
            'day1',
            'day2',
            'day3',
            'day4',
            'day5',
            'day6',
        ];

        $totalsByHour = [];

        foreach ($hourlyTotalCategories as $category) {
            $categoryHours = [];
            for ($i = 0; $i < 24; $i++) {
                $categoryHours[$i] = [];
            }
            $totalsByHour[$category] = $categoryHours;
        }

        foreach ($hourlyStats as $stat) {
            /** @var CarbonImmutable $statTime */
            $statTime = $stat['moment'];
            $statTime = $statTime->shiftTimezone($stationTz);

            $hour = $statTime->hour;

            $statValue = $stat[$statKey];

            $totalsByHour['all'][$hour][] = $statValue;

            $dayOfWeek = 'day' . ((int)$statTime->format('N') - 1);
            $totalsByHour[$dayOfWeek][$hour][] = $statValue;
        }

        $hourlyCharts = [];

        foreach ($hourlyTotalCategories as $category) {
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
                $totals = $totalsByHour[$category][$i] ?? [];
                if (0 === count($totals)) {
                    $totals = [0];
                }

                $statValue = ($statKey === 'number_unique')
                    ? array_sum($totals)
                    : round(array_sum($totals) / count($totals), 2);

                $hourlyRows[] = $statValue;

                $hourlyAlt['values'][] = [
                    'label' => $i . ':00',
                    'type' => 'string',
                    'value' => $statValue . ' ' . __('Listeners'),
                ];
            }

            $hourlyChart->data = $hourlyRows;

            $hourlyCharts[$category] = [
                'labels' => $hourlyLabels,
                'metrics' => [
                    $hourlyChart,
                ],
                'alt' => [
                    $hourlyAlt,
                ],
            ];
        }

        $stats['hourly'] = $hourlyCharts;

        return $response->withJson($stats);
    }
}
