<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

final class ScenarioCase
{
    /**
     * @param array<string, bool> $expectShouldPlay Keyed by playlist ref
     * @param array<string, bool> $expectSchedulePlay Keyed by <playlistRef>#<scheduleIndex>
     * @param ExpectSequenceStep[] $expectSequence
     */
    public function __construct(
        public readonly ?string $name,
        public readonly string $now,
        public readonly ?int $seed,
        public readonly ScenarioRuntime $runtime,
        public readonly array $expectShouldPlay,
        public readonly array $expectSchedulePlay,
        public readonly array $expectSequence,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            name: Types::stringOrNull($data['name'] ?? null),
            now: Types::string($data['now'] ?? null),
            seed: array_key_exists('seed', $data) ? Types::int($data['seed']) : null,
            runtime: ScenarioRuntime::fromArray(Types::array($data['runtime'] ?? [])),
            expectShouldPlay: array_map(
                static fn(mixed $expected): bool => Types::bool($expected),
                Types::array($data['expect_should_play'] ?? [])
            ),
            expectSchedulePlay: array_map(
                static fn(mixed $expected): bool => Types::bool($expected),
                Types::array($data['expect_schedule_play'] ?? [])
            ),
            expectSequence: array_map(
                static fn(mixed $step): ExpectSequenceStep => ExpectSequenceStep::fromArray(Types::array($step)),
                array_values(Types::array($data['expect_sequence'] ?? []))
            ),
        );
    }

    public function __toString(): string
    {
        return $this->name ?? '(unnamed case)';
    }
}
