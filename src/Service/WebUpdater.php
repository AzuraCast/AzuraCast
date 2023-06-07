<?php

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use RuntimeException;

final class WebUpdater
{
    use EnvironmentAwareTrait;

    // Don't worry that this is insecure; it's only ever used for internal communications.
    public const WATCHTOWER_TOKEN = 'azur4c457';

    public function __construct(
        private readonly GuzzleFactory $guzzleFactory
    ) {
    }

    public function isSupported(): bool
    {
        return $this->environment->enableWebUpdater();
    }

    public function triggerUpdate(): void
    {
        if (!$this->isSupported()) {
            throw new RuntimeException('Web updates are not supported on this installation.');
        }

        $client = $this->guzzleFactory->buildClient();

        $client->post(
            'http://updater:8080/v1/update',
            [
                'timeout' => 0,
                'headers' => [
                    'Authorization' => 'Bearer ' . self::WATCHTOWER_TOKEN,
                ],
            ]
        );
    }
}
