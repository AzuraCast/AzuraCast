<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class MountsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        $settings = $settingsRepo->readSettings();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Mount Points'),
                'id' => 'station-mounts',
                'component' => 'Vue_StationsMounts',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:stations:mounts'),
                    'newIntroUrl' => (string)$router->fromHere('api:stations:mounts:new-intro'),
                    'stationFrontendType' => $station->getFrontendType(),
                    'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
                ],
            ]
        );
    }
}
