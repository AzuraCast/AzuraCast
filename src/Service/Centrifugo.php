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

    public function publishToStation(Station $station, mixed $message, array $triggers): void
    {
        $this->send([
            'method' => 'publish',
            'params' => [
                'channel' => $this->getChannelName($station),
                'data' => [
                    'np' => $message,
                    'triggers' => $triggers,
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

    public function getChannelName(Station $station): string
    {
        return 'station:' . $station->getShortName();
    }
}
