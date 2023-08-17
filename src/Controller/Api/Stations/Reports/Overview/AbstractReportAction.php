<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\AnalyticsLevel;

abstract class AbstractReportAction implements SingleActionInterface
{
    use AcceptsDateRange;
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    protected function isAllAnalyticsEnabled(): bool
    {
        return AnalyticsLevel::All === $this->readSettings()->getAnalytics();
    }

    protected function isAnalyticsEnabled(): bool
    {
        return $this->readSettings()->isAnalyticsEnabled();
    }

    protected function buildChart(
        array $rows,
        string $valueLabel,
        ?int $limitResults = 10
    ): array {
        arsort($rows);

        $topRows = (null !== $limitResults)
            ? array_slice($rows, 0, $limitResults)
            : $rows;

        $alt = [
            'label' => $valueLabel,
            'values' => [],
        ];

        $labels = [];
        $data = [];

        foreach ($topRows as $key => $value) {
            $labels[] = $key;
            $data[] = (int)$value;

            $alt['values'][] = [
                'label' => $key,
                'type' => 'string',
                'value' => $value . ' ' . $valueLabel,
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $valueLabel,
                    'data' => $data,
                ],
            ],
            'alt' => [
                $alt,
            ],
        ];
    }
}
