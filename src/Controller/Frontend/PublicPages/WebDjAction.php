<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Enums\StationFeatures;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Psr\Http\Message\ResponseInterface;

final class WebDjAction implements SingleActionInterface
{
    public function __construct(
        private readonly Adapters $adapters,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        if (!$station->getEnablePublicPage()) {
            throw NotFoundException::station();
        }

        StationFeatures::Streamers->assertSupportedForStation($station);

        $backend = $this->adapters->requireBackendAdapter($station);

        $wssUrl = (string)$backend->getWebStreamingUrl($station, $request->getRouter()->getBaseUrl());

        return $request->getView()->renderToResponse(
            response: $response->withHeader('X-Frame-Options', '*'),
            templateName: 'frontend/public/webdj',
            templateArgs: [
                'station' => $station,
                'wss_url' => $wssUrl,
            ]
        );
    }
}
