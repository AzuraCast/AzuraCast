<?php

declare(strict_types=1);

namespace App\Service;

use App\Container\EnvironmentAwareTrait;
use RuntimeException;
use Throwable;

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

    public function ping(): bool
    {
        if (!$this->isSupported()) {
            return false;
        }

        try {
            $client = $this->guzzleFactory->buildClient();
            $client->get(
                'http://updater:8080/',
                [
                    'http_errors' => false,
                    'timeout' => 5,
                ]
            );

            return true;
        } catch (Throwable) {
            return false;
        }
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
