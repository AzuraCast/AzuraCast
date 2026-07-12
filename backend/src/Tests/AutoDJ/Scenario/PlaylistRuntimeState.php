<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

/**
 * Runtime override for a single playlist keyed by playlist ref
 */
final class PlaylistRuntimeState
{
    public function __construct(
        public readonly bool $hasPlayedAt,
        public readonly ?string $playedAt,
        public readonly bool $hasQueueResetAt,
        public readonly ?string $queueResetAt,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            hasPlayedAt: array_key_exists('played_at', $data),
            playedAt: Types::stringOrNull($data['played_at'] ?? null),
            hasQueueResetAt: array_key_exists('queue_reset_at', $data),
            queueResetAt: Types::stringOrNull($data['queue_reset_at'] ?? null),
        );
    }
}
