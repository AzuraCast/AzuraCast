<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Time;
use App\Utilities\Types;

/**
 * A single recently played entry in a scenario's "queue_history" runtime section for the in-memory store.
 * The raw metadata here is fallback for history rows that reference media not in the dump.
 */
final class QueueHistoryEntry
{
    public function __construct(
        public readonly ?string $mediaRef,
        public readonly ?string $songId,
        public readonly ?string $artist,
        public readonly ?string $title,
        public readonly int $timestampPlayed,
        public readonly ?string $playlistRef,
        public readonly bool $isVisible,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $timestamp = $data['timestamp_played'] ?? null;

        return new self(
            mediaRef: Types::stringOrNull($data['media_ref'] ?? null),
            songId: Types::stringOrNull($data['song_id'] ?? null),
            artist: Types::stringOrNull($data['artist'] ?? null),
            title: Types::stringOrNull($data['title'] ?? null),
            timestampPlayed: ($timestamp !== null) ? Time::toUtcCarbonImmutable($timestamp)->getTimestamp() : 0,
            playlistRef: Types::stringOrNull($data['playlist_ref'] ?? null),
            isVisible: Types::bool($data['is_visible'] ?? true),
        );
    }
}
