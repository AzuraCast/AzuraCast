<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Cache\NowPlayingCache;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Centrifugo;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class CentrifugoAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly NowPlayingCache $npCache,
        private readonly Centrifugo $centrifugo,
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $router = $request->getRouter();
        $baseUrl = $router->getBaseUrl();

        $parsedBody = Types::array($request->getParsedBody());
        $this->logger->debug('Centrifugo connection body', $parsedBody);

        $channels = array_filter(
            $parsedBody['channels'] ?? [],
            fn($channel) => str_starts_with($channel, 'station:')
        );

        $allInitialData = [];
        foreach ($channels as $channel) {
            $stationName = substr($channel, 8);
            $np = $this->npCache->getForStation($stationName);
            if (!($np instanceof NowPlaying)) {
                continue;
            }

            $np->resolveUrls($baseUrl);
            $np->update();

            $allInitialData[] = [
                'channel' => $channel,
                'pub' => [
                    'data' => $this->centrifugo->buildStationMessage($np),
                ],
            ];
        }

        return $response->withJson([
            'result' => [
                'user' => '',
                'channels' => $channels,
                'data' => $allInitialData,
            ],
        ]);
    }
}
