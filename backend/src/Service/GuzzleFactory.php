<?php

declare(strict_types=1);

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;

final class GuzzleFactory
{
    public function __construct(
        private readonly array $defaultConfig = []
    ) {
    }

    public function withConfig(array $defaultConfig): self
    {
        return new self($defaultConfig);
    }

    public function withAddedConfig(array $config): self
    {
        return new self(array_merge($this->defaultConfig, $config));
    }

    public function getDefaultConfig(): array
    {
        return $this->defaultConfig;
    }

    public function getHandlerStack(): HandlerStack
    {
        return $this->defaultConfig['handler'] ?? HandlerStack::create();
    }

    public function buildClient(array $config = []): Client
    {
        return new Client(array_merge($this->defaultConfig, $config));
    }
}
