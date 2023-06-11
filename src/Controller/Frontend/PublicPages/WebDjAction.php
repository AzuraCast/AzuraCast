<?php

declare(strict_types=1);

namespace App\Controller\Frontend\PublicPages;

use App\Controller\SingleActionInterface;
use App\Exception\StationNotFoundException;
use App\Exception\StationUnsupportedException;
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
            throw new StationNotFoundException();
        }

        if (!$station->getEnableStreamers()) {
            throw new StationUnsupportedException();
        }

        $backend = $this->adapters->getBackendAdapter($station);
        if (null === $backend) {
            throw new StationUnsupportedException();
        }

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
