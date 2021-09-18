<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class RemotesAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo
    ): ResponseInterface {
        $router = $request->getRouter();

        $settings = $settingsRepo->readSettings();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Remote Relays'),
                'id' => 'station-remotes',
                'component' => 'Vue_StationsRemotes',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:stations:remotes'),
                    'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
                ],
            ]
        );
    }
}
