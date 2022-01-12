<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Config;
use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class WebhooksAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        SettingsRepository $settingsRepo,
        Config $config
    ): ResponseInterface {
        $router = $request->getRouter();

        $settings = $settingsRepo->readSettings();

        $webhookConfig = $config->get('webhooks');

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsWebhooks',
            id: 'station-webhooks',
            title: __('Web Hooks'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:webhooks'),
                'webhookTypes' => $webhookConfig['webhooks'],
                'webhookTriggers' => $webhookConfig['triggers'],
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ]
        );
    }
}
