<?php

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BuildDoctrineMappingPaths extends Event
{
    protected array $mappingClassesPaths;
    protected string $baseDir;

    public function __construct(array $mappingClassesPaths, string $baseDir)
    {
        $this->mappingClassesPaths = $mappingClassesPaths;
        $this->baseDir = $baseDir;
    }

    /**
     * @return string[]
     */
    public function getMappingClassesPaths(): array
    {
        return $this->mappingClassesPaths;
    }

    public function setMappingClassesPaths(array $mappingClassesPaths): void
    {
        $this->mappingClassesPaths = $mappingClassesPaths;
    }

    public function getBaseDir(): string
    {
        return $this->baseDir;
    }
}
