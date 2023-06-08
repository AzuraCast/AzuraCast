<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Container\SettingsAwareTrait;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class MountsAction
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();
        $station = $request->getStation();

        $settings = $this->readSettings();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsMounts',
            id: 'station-mounts',
            title: __('Mount Points'),
            props: [
                'listUrl' => $router->fromHere('api:stations:mounts'),
                'newIntroUrl' => $router->fromHere('api:stations:mounts:new-intro'),
                'restartStatusUrl' => $router->fromHere('api:stations:restart-status'),
                'stationFrontendType' => $station->getFrontendType()->value,
                'showAdvanced' => $settings->getEnableAdvancedFeatures(),
            ],
        );
    }
}
