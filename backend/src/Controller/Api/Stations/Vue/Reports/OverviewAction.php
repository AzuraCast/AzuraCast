<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue\Reports;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\AnalyticsLevel;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class OverviewAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        // Get current analytics level.
        $settings = $this->readSettings();

        if (!$settings->isAnalyticsEnabled()) {
            throw new RuntimeException('Analytics are not enabled for this station.');
        }

        $analyticsLevel = $settings->getAnalytics();

        return $response->withJson([
            'showFullAnalytics' => AnalyticsLevel::All === $analyticsLevel,
        ]);
    }
}
