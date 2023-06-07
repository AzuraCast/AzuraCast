<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Station;
use GuzzleHttp\Client;

final class Centrifugo
{
    use EnvironmentAwareTrait;

    public const GLOBAL_TIME_CHANNEL = 'global:time';

    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function isSupported(): bool
    {
        return $this->environment->isDocker() && !$this->environment->isTesting();
    }

    public function sendTime(): void
    {
        $this->send([
            'method' => 'publish',
            'params' => [
                'channel' => self::GLOBAL_TIME_CHANNEL,
                'data' => [
                    'time' => time(),
                ],
            ],
        ]);
    }

    public function publishToStation(Station $station, mixed $message): void
    {
        $this->send([
            'method' => 'publish',
            'params' => [
                'channel' => $this->getChannelName($station),
                'data' => [
                    'np' => $message,
                ],
            ],
        ]);
    }

    private function send(array $body): void
    {
        $this->client->post(
            'http://localhost:6025/api',
            [
                'json' => $body,
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
                                $this->getChannelName($station) => [],
                                self::GLOBAL_TIME_CHANNEL => [],
                            ],
                        ],
                        JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR | JSON_FORCE_OBJECT
                    ),
                ]
        );
    }

    public function getChannelName(Station $station): string
    {
        return 'station:' . $station->getShortName();
    }
}
