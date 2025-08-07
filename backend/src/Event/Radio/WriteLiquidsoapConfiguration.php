<?php

declare(strict_types=1);

namespace App\Event\Radio;

use App\Entity\Station;
use App\Entity\StationBackendConfiguration;
use App\Event\AbstractConfigurationEvent;

final class WriteLiquidsoapConfiguration extends AbstractConfigurationEvent
{
    private StationBackendConfiguration $backendConfig;

    public function __construct(
        private readonly Station $station,
        private readonly bool $forEditing = false,
        private readonly bool $writeToDisk = true
    ) {
        $this->backendConfig = $station->backend_config;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getBackendConfig(): StationBackendConfiguration
    {
        return $this->backendConfig;
    }

    public function isForEditing(): bool
    {
        return $this->forEditing;
    }

    public function shouldWriteToDisk(): bool
    {
        return $this->writeToDisk && !$this->forEditing;
    }
}
