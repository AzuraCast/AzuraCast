<?php

declare(strict_types=1);

namespace App\Event;

use Symfony\Contracts\EventDispatcher\Event;

class BuildDoctrineMappingPaths extends Event
{
    public function __construct(
        protected array $mappingClassesPaths,
        protected string $baseDir
    ) {
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
