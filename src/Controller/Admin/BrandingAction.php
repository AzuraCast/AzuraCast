<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Assets\AssetFactory;
use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class BrandingAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminBranding',
            id: 'admin-branding',
            title: __('Custom Branding'),
            props: [
                'settingsApiUrl'    => (string)$router->named('api:admin:settings', [
                    'group' => Settings::GROUP_BRANDING,
                ]),
                'browserIconApiUrl' => (string)$router->named('api:admin:custom_assets', [
                    'type' => AssetFactory::TYPE_BROWSER_ICON,
                ]),
                'backgroundApiUrl'  => (string)$router->named('api:admin:custom_assets', [
                    'type' => AssetFactory::TYPE_BACKGROUND,
                ]),
                'albumArtApiUrl'    => (string)$router->named('api:admin:custom_assets', [
                    'type' => AssetFactory::TYPE_ALBUM_ART,
                ]),
            ],
        );
    }
}
