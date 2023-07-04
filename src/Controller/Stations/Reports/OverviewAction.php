<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\AnalyticsLevel;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

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
            // The entirety of the dashboard can't be shown, so redirect user to the profile page.
            return $request->getView()->renderToResponse($response, 'stations/reports_restricted');
        }

        $router = $request->getRouter();
        $analyticsLevel = $settings->getAnalytics();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsReportsOverview',
            id: 'vue-reports-overview',
            title: __('Station Statistics'),
            props: [
                'stationTimeZone' => $request->getStation()->getTimezone(),
                'showFullAnalytics' => AnalyticsLevel::All === $analyticsLevel,
                'listenersByTimePeriodUrl' => $router->fromHere('api:stations:reports:overview-charts'),
                'bestAndWorstUrl' => $router->fromHere('api:stations:reports:best-and-worst'),
                'byStreamUrl' => $router->fromHere('api:stations:reports:by-stream'),
                'byBrowserUrl' => $router->fromHere('api:stations:reports:by-browser'),
                'byCountryUrl' => $router->fromHere('api:stations:reports:by-country'),
                'byClientUrl' => $router->fromHere('api:stations:reports:by-client'),
                'listeningTimeUrl' => $router->fromHere('api:stations:reports:by-listening-time'),
            ]
        );
    }
}
