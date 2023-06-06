<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Entity\Enums\AnalyticsLevel;
use App\Entity\Repository\SettingsRepository;

abstract class AbstractReportAction
{
    use AcceptsDateRange;
    use EntityManagerAwareTrait;

    public function __construct(
        protected readonly SettingsRepository $settingsRepo
    ) {
    }

    protected function isAllAnalyticsEnabled(): bool
    {
        return AnalyticsLevel::All === $this->settingsRepo->readSettings()->getAnalytics();
    }

    protected function isAnalyticsEnabled(): bool
    {
        return $this->settingsRepo->readSettings()->isAnalyticsEnabled();
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
