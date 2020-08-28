<?php
namespace App\Webhook;

use App\Webhook\Connector\ConnectorInterface;
use Doctrine\Common\Collections\ArrayCollection;

class ConnectorCollection extends ArrayCollection
{
    public function getConnector(string $name): ConnectorInterface
    {
        if ($this->offsetExists($name)) {
            return $this->get($name);
        }

        throw new \InvalidArgumentException('Invalid web hook connector type specified.');
    }
}