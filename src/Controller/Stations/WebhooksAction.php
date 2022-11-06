<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Webhook\ConnectorLocator;
use Psr\Http\Message\ResponseInterface;

final class WebhooksAction
{
    public function __construct(
        private readonly SettingsRepository $settingsRepo,
        private readonly ConnectorLocator $connectorLocator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $router = $request->getRouter();

        $settings = $this->settingsRepo->readSettings();

        $webhookConfig = $this->connectorLocator->getWebhookConfig();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsWebhooks',
            id: 'station-webhooks',
            title: __('Web Hooks'),
            props: [
                'listUrl' => $router->fromHere('api:stations:webhooks'),
                'webhookTypes' => $webhookConfig['webhooks'],
                'webhookTriggers' => $webhookConfig['triggers'],
                'enableAdvancedFeatures' => $settings->getEnableAdvancedFeatures(),
            ]
        );
    }
}
