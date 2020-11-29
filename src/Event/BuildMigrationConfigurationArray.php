<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BuildMigrationConfigurationArray extends Event
{
    protected array $migrationConfigurations;
    protected string $baseDir;

    public function __construct(array $migrationConfigurations, string $baseDir)
    {
        $this->migrationConfigurations = $migrationConfigurations;
        $this->baseDir = $baseDir;
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
