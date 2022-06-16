<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Reports\Overview;

use App\Controller\Api\Traits\AcceptsDateRange;
use App\Entity\Enums\AnalyticsLevel;
use App\Entity\Repository\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;

abstract class AbstractReportAction
{
    use AcceptsDateRange;

    public function __construct(
        protected readonly SettingsRepository $settingsRepo,
        protected readonly EntityManagerInterface $em,
    ) {
    }

    protected function isAllAnalyticsEnabled(): bool
    {
        return AnalyticsLevel::All === $this->settingsRepo->readSettings()->getAnalyticsEnum();
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

        $alt = ['<dl>'];
        $labels = [];
        $data = [];

        foreach ($topRows as $key => $value) {
            $labels[] = $key;
            $data[] = (int)$value;

            $alt[] = '<dt>' . $key . '</dt>';
            $alt[] = '<dd>' . $value . ' ' . $valueLabel . '</dd>';
        }

        $alt[] = '</dl>';

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => $valueLabel,
                    'data' => $data,
                ],
            ],
            'alt' => implode('', $alt),
        ];
    }
}
