<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class MountsAction
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        $settings = $this->settingsRepo->readSettings();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsMounts',
            id: 'station-mounts',
            title: __('Mount Points'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:mounts'),
                'newIntroUrl' => (string)$router->fromHere('api:stations:mounts:new-intro'),
                'restartStatusUrl' => (string)$router->fromHere('api:stations:restart-status'),
                'stationFrontendType' => $station->getFrontendType(),
                'showAdvanced' => $settings->getEnableAdvancedFeatures(),
            ],
        );
    }
}
