<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Station;
use GuzzleHttp\Client;

final class Centrifugo
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly Client $client,
    ) {
    }

    public function isSupported(): bool
    {
        return !$this->environment->isTesting();
    }

    public function publishToStation(Station $station, mixed $message, array $triggers): void
    {
        $this->send([
            'method' => 'publish',
            'params' => [
                'channel' => $this->getChannelName($station),
                'data' => $this->buildStationMessage($message, $triggers),
            ],
        ]);
    }

    public function buildStationMessage(mixed $message, array $triggers = []): array
    {
        return [
            'np' => $message,
            'triggers' => $triggers,
            'current_time' => time(),
        ];
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

    public function getChannelName(Station $station): string
    {
        return 'station:' . $station->short_name;
    }
}
