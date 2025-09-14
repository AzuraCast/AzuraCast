<?php

declare(strict_types=1);

namespace App\Event\Nginx;

use App\Entity\Station;
use App\Event\AbstractConfigurationEvent;

final class WriteNginxConfiguration extends AbstractConfigurationEvent
{
    public function __construct(
        private readonly Station $station,
        private readonly bool $writeToDisk = true
    ) {
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function shouldWriteToDisk(): bool
    {
        return $this->writeToDisk;
    }
}
