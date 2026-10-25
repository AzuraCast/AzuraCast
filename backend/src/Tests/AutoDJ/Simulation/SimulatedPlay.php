<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Simulation;

use Carbon\CarbonImmutable;

final readonly class SimulatedPlay
{
    public function __construct(
        public int $build,
        public CarbonImmutable $playedAt,
        public ?string $playlistRef,
        public ?string $mediaRef,
        public string $artist,
        public string $title,
        public float $duration,
        public PickKind $pickKind,
        public int $queueResets,
    ) {
    }
}
