<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Webhook\Connector\ConnectorInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

final class ConnectorLocator
{
    private array $connectors;

    public function __construct(
        private readonly ContainerInterface $di,
        private readonly array $webhookConfig,
    ) {
        $connectors = [];
        foreach ($webhookConfig['webhooks'] as $webhook_key => $webhook_info) {
            $connectors[$webhook_key] = $webhook_info['class'];
        }

        $this->connectors = $connectors;
    }

    public function getWebhookConfig(): array
    {
        return $this->webhookConfig;
    }

    public function getConnector(string $name): ConnectorInterface
    {
        if (!isset($this->connectors[$name])) {
            throw new InvalidArgumentException('Invalid web hook connector type specified.');
        }

        $connectorClass = $this->connectors[$name];
        return $this->di->get($connectorClass);
    }
}
