<?php

declare(strict_types=1);

namespace App\Controller\Stations\Reports;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocation;
use Psr\Http\Message\ResponseInterface;

class ListenersController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Entity\Repository\SettingsRepository $settingsRepo,
        IpGeolocation $ipGeo
    ): ResponseInterface {
        $view = $request->getView();

        $analytics_level = $settingsRepo->readSettings()->getAnalytics();

        if ($analytics_level !== Entity\Analytics::LEVEL_ALL) {
            return $view->renderToResponse($response, 'stations/reports/restricted');
        }

        $attribution = $ipGeo->getAttribution();
        return $view->renderToResponse(
            $response,
            'stations/reports/listeners',
            [
                'attribution' => $attribution,
            ]
        );
    }
}
