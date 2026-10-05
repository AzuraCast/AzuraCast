<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

final class ScenarioSimulation
{
    /**
     * @param string[] $measurePlaylistRefs
     * @param array<string, mixed> $backendConfig
     */
    public function __construct(
        public readonly int $measuredHours,
        public readonly int $historyHours,
        public readonly array $measurePlaylistRefs,
        public readonly array $backendConfig,
        public readonly SimulationExpectations $expect,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            measuredHours: Types::int($data['measured_hours'] ?? null),
            historyHours: Types::int($data['history_hours'] ?? null),
            measurePlaylistRefs: array_map(
                static fn(mixed $ref): string => Types::string($ref),
                array_values(Types::array($data['measure_playlists'] ?? []))
            ),
            backendConfig: Types::array($data['backend_config'] ?? []),
            expect: SimulationExpectations::fromArray(Types::array($data['expect'] ?? [])),
        );
    }
}
