<?php

namespace App\Controller\Frontend\PWA;

use App\Environment;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class AppManifestAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $iconBaseUrl = $environment->getAssetUrl() . '/icons/' . $environment->getAppEnvironment();

        $manifest = [
            'name' => $station->getName() . ' - AzuraCast',
            'short_name' => $station->getName(),
            'description' => $station->getDescription(),
            'start_url' => '.',
            'display' => 'standalone',
            'theme_color' => '#2196F3',
            'categories' => [
                'music',
            ],
            'icons' => [
                [
                    'src' => $iconBaseUrl . '/android-icon-36x36.png',
                    'sizes' => '36x36',
                    'type' => 'image/png',
                    'density' => '0.75',
                ],
                [
                    'src' => $iconBaseUrl . '/android-icon-48x48.png',
                    'sizes' => '48x48',
                    'type' => 'image/png',
                    'density' => '1.0',
                ],
                [
                    'src' => $iconBaseUrl . '/android-icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'density' => '1.5',
                ],
                [
                    'src' => $iconBaseUrl . '/android-icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                    'density' => '2.0',
                ],
                [
                    'src' => $iconBaseUrl . '/android-icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                    'density' => '3.0',
                ],
                [
                    'src' => $iconBaseUrl . '/android-icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'density' => '4.0',
                ],
            ],
        ];

        $customization = $request->getCustomization();
        $publicTheme = $customization->getPublicTheme();

        if ($customization::THEME_BROWSER !== $publicTheme) {
            $manifest['background_color'] = match ($publicTheme) {
                $customization::THEME_DARK => '#222222',
                default => '#EEEEEE'
            };
        }

        return $response
            ->withHeader('Content-Type', 'application/manifest+json')
            ->withJson($manifest);
    }
}
