<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BuildMigrationConfigurationArray extends Event
{
    public function __construct(
        protected array $migrationConfigurations,
        protected string $baseDir
    ) {
    }

    /**
     * @return mixed[]
     */
    public function getMigrationConfigurations(): array
    {
        return $this->migrationConfigurations;
    }

    public function setMigrationConfigurations(array $migrationConfigurations): void
    {
        $this->migrationConfigurations = $migrationConfigurations;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }
}
