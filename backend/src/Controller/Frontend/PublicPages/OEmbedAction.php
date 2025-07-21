<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Xml\Writer;
use Psr\Http\Message\ResponseInterface;

final class OEmbedAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->enable_public_page) {
            throw NotFoundException::station();
        }

        $format = $params['format'] ?? 'json';

        $router = $request->getRouter();

        $embedUrl = $router->named(
            'public:index',
            ['station_id' => $station->short_name, 'embed' => 'social'],
            [],
            true
        );

        $result = [
            'version' => '1.0',
            'title' => $station->name,
            'thumbnail_url' => $router->named(
                routeName: 'api:nowplaying:art',
                routeParams: ['station_id' => $station->short_name, 'timestamp' => time()],
                absolute: true
            ),
            'thumbnail_width' => 128,
            'thumbnail_height' => 128,
            'provider_name' => 'AzuraCast',
            'provider_url' => 'https://azuracast.com/',
            'type' => 'rich',
            'width' => 400,
            'height' => 200,
            'html' => <<<HTML
                <iframe width="100%" height="200" sandbox="allow-same-origin allow-scripts allow-popups" 
                    src="$embedUrl" frameborder="0" allowfullscreen/>
            HTML,
        ];

        return match ($format) {
            'xml' => $response->write(Writer::toString($result, 'oembed')),
            default => $response->withJson($result)
        };
    }
}
