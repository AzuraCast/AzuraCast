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
        private readonly NowPlayingCache $npCache
    ) {
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $parsedBody = Types::array($request->getParsedBody());
        $this->logger->debug('Centrifugo connection body', $parsedBody);

        $channels = array_filter(
            $parsedBody['channels'] ?? [],
            fn($channel) => str_starts_with($channel, 'station:') || $channel === Centrifugo::GLOBAL_TIME_CHANNEL
        );

        $allInitialData = [];
        $baseUrl = $request->getRouter()->getBaseUrl();

        foreach ($channels as $channel) {
            $initialData = [];

            if ($channel === Centrifugo::GLOBAL_TIME_CHANNEL) {
                $initialData['time'] = time();
            } elseif (str_starts_with($channel, 'station:')) {
                $stationName = substr($channel, 8);
                $np = $this->npCache->getForStation($stationName);
                if (!($np instanceof NowPlaying)) {
                    continue;
                }

                $np->resolveUrls($baseUrl);
                $np->update();

                $initialData['np'] = $np;
                $initialData['triggers'] = [];
            }

            $allInitialData[] = [
                'channel' => $channel,
                'pub' => [
                    'data' => $initialData,
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
