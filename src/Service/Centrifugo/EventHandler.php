<?php

declare(strict_types=1);

namespace App\Service\Centrifugo;

use App\Cache\NowPlayingCache;
use App\Container\LoggerAwareTrait;
use App\Entity\Api\NowPlaying\NowPlaying;
use App\Http\RouterInterface;
use App\Service\Centrifugo;
use RoadRunner\Centrifugo\Payload\ConnectResponse;
use RoadRunner\Centrifugo\Payload\ResponseInterface;
use RoadRunner\Centrifugo\Request\Connect;
use RoadRunner\Centrifugo\Request\Invalid;
use RoadRunner\Centrifugo\Request\RequestInterface;
use RuntimeException;
use Throwable;

final class EventHandler
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly NowPlayingCache $npCache,
        private readonly Centrifugo $centrifugo,
        private readonly RouterInterface $router
    ) {
        if (!$this->centrifugo->isSupported()) {
            throw new RuntimeException('Centrifugo is not supported.');
        }

        $this->router->buildBaseUrl(false);
    }

    public function __invoke(RequestInterface $request): ?ResponseInterface
    {
        if ($request instanceof Invalid) {
            $e = $request->getException();
            $this->logger->error(
                sprintf('Centrifugo error: %s', $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );
            return null;
        }

        try {
            if ($request instanceof Connect) {
                return $this->onConnect($request);
            }
        } catch (Throwable $e) {
            $request->error($e->getCode(), $e->getMessage());
        }

        return null;
    }

    private function onConnect(Connect $request): ?ResponseInterface
    {
        $channels = array_filter(
            $request->channels,
            fn($channel) => str_starts_with($channel, 'station:') || $channel === Centrifugo::GLOBAL_TIME_CHANNEL
        );

        if (empty($channels)) {
            return null;
        }

        $allInitialData = [];

        foreach ($channels as $channel) {
            if ($channel === Centrifugo::GLOBAL_TIME_CHANNEL) {
                $initialData = $this->centrifugo->buildTimeMessage();
            } elseif (str_starts_with($channel, 'station:')) {
                $stationName = substr($channel, 8);
                $np = $this->npCache->getForStation($stationName);
                if (!($np instanceof NowPlaying)) {
                    continue;
                }

                $np->resolveUrls($this->router->getBaseUrl());
                $np->update();

                $initialData = $this->centrifugo->buildStationMessage($np);
            } else {
                continue;
            }

            $allInitialData[] = [
                'channel' => $channel,
                'pub' => [
                    'data' => $initialData,
                ],
            ];
        }

        return new ConnectResponse(
            user: '',
            data: $allInitialData,
            channels: $channels
        );
    }
}
