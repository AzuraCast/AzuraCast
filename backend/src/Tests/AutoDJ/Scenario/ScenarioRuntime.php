<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

final class ScenarioRuntime
{
    /**
     * @param array<string, PlaylistRuntimeState> $playlists Keyed by playlist ref
     * @param array<string, PlaylistMediaRuntimeState> $playlistMedia Keyed by "<playlistRef>:<mediaRef>"
     * @param array<string, GroupMemberRuntimeState> $groupMembers Keyed by "<containerRef>:<memberRef>"
     * @param CuedMediaEntry[] $cuedMedia Unplayed queue entries in cue order, oldest first
     * @param QueueHistoryEntry[] $queueHistory Recently played entries, in fixture order
     */
    public function __construct(
        public readonly array $playlists,
        public readonly array $playlistMedia,
        public readonly array $groupMembers,
        public readonly array $cuedMedia,
        public readonly array $queueHistory,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            playlists: array_map(
                static fn(mixed $item): PlaylistRuntimeState => PlaylistRuntimeState::fromArray(Types::array($item)),
                Types::array($data['playlists'] ?? [])
            ),
            playlistMedia: array_map(
                static fn(mixed $item): PlaylistMediaRuntimeState
                    => PlaylistMediaRuntimeState::fromArray(Types::array($item)),
                Types::array($data['playlist_media'] ?? [])
            ),
            groupMembers: array_map(
                static fn(mixed $item): GroupMemberRuntimeState
                    => GroupMemberRuntimeState::fromArray(Types::array($item)),
                Types::array($data['group_members'] ?? [])
            ),
            cuedMedia: array_map(
                static fn(mixed $item): CuedMediaEntry => CuedMediaEntry::fromArray(Types::array($item)),
                array_values(Types::array($data['cued_media'] ?? []))
            ),
            queueHistory: array_map(
                static fn(mixed $item): QueueHistoryEntry => QueueHistoryEntry::fromArray(Types::array($item)),
                array_values(Types::array($data['queue_history'] ?? []))
            ),
        );
    }
}
