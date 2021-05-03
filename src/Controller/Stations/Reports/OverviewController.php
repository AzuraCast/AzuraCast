<?php

namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class OverviewController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\SettingsRepository $settingsRepo
    ): ResponseInterface {
        // Get current analytics level.
        $settings = $settingsRepo->readSettings();
        $analytics_level = $settings->getAnalytics();

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            // The entirety of the dashboard can't be shown, so redirect user to the profile page.
            return $request->getView()->renderToResponse($response, 'stations/reports/restricted');
        }

        return $request->getView()->renderToResponse(
            $response,
            'stations/reports/overview'
        );
    }
}
