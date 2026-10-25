<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Tests\AutoDJ\Scenario\Enums\ScenarioMode;
use App\Utilities\Types;

final class ScenarioFile
{
    /**
     * @param ScenarioMode[] $modes
     * @param ScenarioCase[] $cases
     */
    public function __construct(
        public readonly ?string $description,
        public readonly array $modes,
        public readonly array $cases,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $modes = array_key_exists('modes', $data)
            ? array_filter(array_map(
                static fn(mixed $mode): ?ScenarioMode => ScenarioMode::tryFrom(Types::string($mode)),
                Types::array($data['modes'])
            ))
            : [ScenarioMode::InMemory, ScenarioMode::Integration];

        return new self(
            description: Types::stringOrNull($data['description'] ?? null),
            modes: $modes,
            cases: array_map(
                static fn(mixed $case): ScenarioCase => ScenarioCase::fromArray(Types::array($case)),
                array_values(Types::array($data['cases'] ?? []))
            ),
        );
    }
}
