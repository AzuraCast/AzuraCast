<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Assets\AssetTypes;
use App\Controller\SingleActionInterface;
use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class BrandingAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/Branding',
            id: 'admin-branding',
            title: __('Custom Branding'),
            props: [
                'settingsApiUrl' => $router->named('api:admin:settings', [
                    'group' => Settings::GROUP_BRANDING,
                ]),
                'browserIconApiUrl' => $router->named('api:admin:custom_assets', [
                    'type' => AssetTypes::BrowserIcon->value,
                ]),
                'backgroundApiUrl' => $router->named('api:admin:custom_assets', [
                    'type' => AssetTypes::Background->value,
                ]),
                'albumArtApiUrl' => $router->named('api:admin:custom_assets', [
                    'type' => AssetTypes::AlbumArt->value,
                ]),
            ],
        );
    }
}
