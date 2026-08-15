<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Time;
use App\Utilities\Types;

/**
 * A single song request in a scenario's "requests" runtime section.
 *
 * Requests default to "skip_delay" so their eligibility is deterministic due to the
 * StationRequest::shouldPlayNow using random_int to delay requests a bit.
 */
final class RequestEntry
{
    public function __construct(
        public readonly string $mediaRef,
        public readonly bool $skipDelay,
        public readonly ?int $timestamp,
        public readonly bool $played,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $timestamp = $data['timestamp'] ?? null;

        return new self(
            mediaRef: Types::string($data['media_ref'] ?? null),
            skipDelay: Types::bool($data['skip_delay'] ?? true),
            timestamp: ($timestamp !== null) ? Time::toUtcCarbonImmutable($timestamp)->getTimestamp() : null,
            played: Types::bool($data['played'] ?? false),
        );
    }
}
