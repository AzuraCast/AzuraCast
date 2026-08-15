<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

/**
 * Runtime override for a single playlist group membership keyed by "<containerRef>:<memberRef>"
 */
final class GroupMemberRuntimeState
{
    public function __construct(
        public readonly ?bool $isQueued,
        public readonly ?int $consecutivePlaysCount,
        public readonly ?int $lastPlayed,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            isQueued: array_key_exists('is_queued', $data)
                ? Types::bool($data['is_queued'])
                : null,
            consecutivePlaysCount: array_key_exists('consecutive_plays_count', $data)
                ? Types::int($data['consecutive_plays_count'])
                : null,
            lastPlayed: array_key_exists('last_played', $data)
                ? Types::int($data['last_played'])
                : null,
        );
    }
}
