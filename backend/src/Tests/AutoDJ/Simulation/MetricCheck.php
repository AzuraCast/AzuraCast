<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Simulation;

use Stringable;

final readonly class MetricCheck implements Stringable
{
    private function __construct(
        public string $label,
        public string $scenarioKey,
        public string $value,
        public ?string $expected,
        public bool $passes
    ) {
    }

    public static function exactly(
        string $label,
        string $scenarioKey,
        int $value,
        ?int $expected
    ): self {
        return new self(
            label: $label,
            scenarioKey: $scenarioKey,
            value: (string) $value,
            expected: ($expected !== null) ? "= {$expected}" : null,
            passes: $expected === null || $value === $expected
        );
    }

    public static function atLeast(
        string $label,
        string $scenarioKey,
        ?float $value,
        ?int $minimum
    ): self {
        return new self(
            label: $label,
            scenarioKey: $scenarioKey,
            value: self::formatNumber($value, 1),
            expected: ($minimum !== null) ? ">= {$minimum}" : null,
            passes: $minimum === null || ($value !== null && $value >= $minimum)
        );
    }

    public static function atMost(
        string $label,
        string $scenarioKey,
        ?float $value,
        ?float $maximum
    ): self {
        return new self(
            label: $label,
            scenarioKey: $scenarioKey,
            value: self::formatNumber($value, 3),
            expected: ($maximum !== null) ? "<= {$maximum}" : null,
            passes: $maximum === null || ($value !== null && $value <= $maximum)
        );
    }

    public function __toString(): string
    {
        return "{$this->label} ({$this->scenarioKey}): {$this->result()}";
    }

    public function verdict(): string
    {
        if (!$this->isExpected()) {
            return 'not checked';
        }

        return $this->passes ? 'ok' : 'FAIL';
    }

    public function isExpected(): bool
    {
        return $this->expected !== null;
    }

    private static function formatNumber(?float $value, int $decimals): string
    {
        return ($value !== null)
            ? number_format($value, $decimals, '.', '')
            : '-';
    }

    private function result(): string
    {
        if (!$this->isExpected()) {
            return $this->value;
        }

        return "{$this->value} (expected {$this->expected}) {$this->verdict()}";
    }
}
