<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Station;
use App\Environment;
use GuzzleHttp\Client;

final class Centrifugo
{
    public function __construct(
        private readonly Environment $environment,
        private readonly Client $client,
    ) {
    }

    public function isSupported(): bool
    {
        return $this->environment->isDocker();
    }

    public function publishToStation(Station $station, mixed $message): void
    {
        $this->client->post(
            'http://localhost:6025/api',
            [
                'json' => [
                    'method' => 'publish',
                    'params' => [
                        'channel' => $this->getChannelName($station),
                        'data' => [
                            'np' => $message,
                        ],
                    ],
                ],
            ]
        );
    }

    public function getSseUrl(Station $station): string
    {
        return '/api/live/nowplaying/sse?' . http_build_query(
                [
                    'cf_connect' => json_encode(
                        [
                            'subs' => [
                                $this->getChannelName($station) => []
                            ],
                        ],
                        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT
                    ),
                ]
            );
    }

    public function getChannelName(Station $station): string
    {
        return 'station:'.$station->getShortName();
    }
}
