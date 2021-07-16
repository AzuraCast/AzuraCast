<?php

declare(strict_types=1);

namespace App\Webhook;

use App\Config;
use App\Webhook\Connector\ConnectorInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class ConnectorLocator
{
    protected array $connectors;

    public function __construct(
        protected ContainerInterface $di,
        Config $config
    ) {
        $webhooks = $config->get('webhooks');
        $connectors = [];
        foreach ($webhooks['webhooks'] as $webhook_key => $webhook_info) {
            $connectors[$webhook_key] = $webhook_info['class'];
        }

        $this->connectors = $connectors;
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
