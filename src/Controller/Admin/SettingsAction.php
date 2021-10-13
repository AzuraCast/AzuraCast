<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;

class SettingsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Version $version
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminSettings',
            id: 'admin-settings',
            title: __('System Settings'),
            props: [
                'apiUrl'         => (string)$router->named('api:admin:settings', [
                    'group' => Settings::GROUP_GENERAL,
                ]),
                'releaseChannel' => $version->getReleaseChannel(),
            ],
        );
    }
}
