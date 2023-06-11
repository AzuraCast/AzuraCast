<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;

final class SettingsAction implements SingleActionInterface
{
    public function __construct(
        private readonly Version $version,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminSettings',
            id: 'admin-settings',
            title: __('System Settings'),
            props: [
                'apiUrl' => $router->named('api:admin:settings', [
                    'group' => Settings::GROUP_GENERAL,
                ]),
                'testMessageUrl' => $router->named('api:admin:send-test-message'),
                'acmeUrl' => $router->named('api:admin:acme'),
                'releaseChannel' => $this->version->getReleaseChannelEnum()->value,
            ],
        );
    }
}
