<?php

declare(strict_types=1);

namespace App\Service\ServiceControl;

final class ServiceData
{
    public function __construct(
        public readonly string $name,
        public readonly string $description,
        public readonly bool $running
    ) {
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'description' => $this->description,
            'running' => $this->running,
        ];
    }
}
