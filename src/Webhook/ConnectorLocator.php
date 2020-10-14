<?php

namespace App\Webhook;

use App\Webhook\Connector\ConnectorInterface;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;

class ConnectorLocator
{
    protected ContainerInterface $di;

    protected array $connectors;

    public function __construct(ContainerInterface $di, array $connectors)
    {
        $this->di = $di;
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
