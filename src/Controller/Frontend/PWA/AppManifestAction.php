<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PWA;

use App\Controller\SingleActionInterface;
use App\Enums\SupportedThemes;
use App\Exception\StationNotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class AppManifestAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw new StationNotFoundException();
        }

        $customization = $request->getCustomization();

        $manifest = [
            'name' => $station->getName() . ' - AzuraCast',
            'short_name' => $station->getName(),
            'description' => $station->getDescription(),
            'scope' => '/public/',
            'start_url' => '.',
            'display' => 'standalone',
            'theme_color' => '#2196F3',
            'categories' => [
                'music',
            ],
            'icons' => [
                [
                    'src' => $customization->getBrowserIconUrl(36),
                    'sizes' => '36x36',
                    'type' => 'image/png',
                    'density' => '0.75',
                ],
                [
                    'src' => $customization->getBrowserIconUrl(48),
                    'sizes' => '48x48',
                    'type' => 'image/png',
                    'density' => '1.0',
                ],
                [
                    'src' => $customization->getBrowserIconUrl(72),
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'density' => '1.5',
                ],
                [
                    'src' => $customization->getBrowserIconUrl(96),
                    'sizes' => '96x96',
                    'type' => 'image/png',
                    'density' => '2.0',
                ],
                [
                    'src' => $customization->getBrowserIconUrl(144),
                    'sizes' => '144x144',
                    'type' => 'image/png',
                    'density' => '3.0',
                ],
                [
                    'src' => $customization->getBrowserIconUrl(192),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'density' => '4.0',
                ],
            ],
        ];

        $customization = $request->getCustomization();
        $publicTheme = $customization->getPublicTheme();

        if (SupportedThemes::Browser !== $publicTheme) {
            $manifest['background_color'] = match ($publicTheme) {
                SupportedThemes::Dark => '#222222',
                default => '#EEEEEE'
            };
        }

        return $response
            ->withHeader('Content-Type', 'application/manifest+json')
            ->withJson($manifest);
    }
}
