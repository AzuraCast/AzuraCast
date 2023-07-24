<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class WebhooksAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        $settings = $this->readSettings();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/Webhooks',
            id: 'station-webhooks',
            title: __('Web Hooks'),
            props: [
                'listUrl' => $router->fromHere('api:stations:webhooks'),
                'nowPlayingUrl' => $router->fromHere('api:nowplaying:index'),
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ]
        );
    }
}
