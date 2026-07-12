<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistGroup;
use App\Entity\StationPlaylistMedia;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;

/**
 * Provides in-memory methods to emulate repository interactions that the Scheduler/QueueBuilder rely on
 *
 * @phpstan-type HistoryEntryShape array{
 *     song_id: string,
 *     text: ?string,
 *     artist: ?string,
 *     title: ?string,
 *     timestamp_played: int,
 *     playlist_ref: ?string,
 *     is_visible: bool
 * }
 */
final class InMemoryAutoDjDataProxy
{
    /** @var ?list<HistoryEntryShape> */
    private ?array $historyCache = null;

    public function __construct(
        private readonly InMemoryEntityStore $entities
    ) {
    }

    // EntityManager

    public function find(string $className, int|string $id): ?object
    {
        $id = (int) $id;

        return match ($className) {
            StationMedia::class => $this->entities->mediaById[$id] ?? null,
            StationPlaylistMedia::class => $this->entities->spmById[$id] ?? null,
            default => null,
        };
    }

    // StationPlaylistMediaRepository

    /**
     * @return StationPlaylistQueue[]
     */
    public function getQueue(StationPlaylist $playlist): array
    {
        /** @var StationPlaylistMedia[] $items */
        $items = $playlist->media_items->toArray();

        if (PlaylistOrders::Random === $playlist->order) {
            shuffle($items);
        } else {
            $items = array_values(
                array_filter($items, static fn(StationPlaylistMedia $spm): bool => $spm->is_queued)
            );

            usort(
                $items,
                static fn(StationPlaylistMedia $a, StationPlaylistMedia $b): int => $a->weight <=> $b->weight
            );
        }

        return array_map(fn(StationPlaylistMedia $spm): StationPlaylistQueue => $this->toPlaylistQueue($spm), $items);
    }

    public function resetQueue(StationPlaylist $playlist, ?CarbonImmutable $now = null): void
    {
        /** @var StationPlaylistMedia[] $items */
        $items = $playlist->media_items->toArray();

        $isShuffle = PlaylistOrders::Shuffle === $playlist->order;
        if ($isShuffle) {
            shuffle($items);
        }

        $weight = 1;
        foreach ($items as $spm) {
            if ($isShuffle) {
                $spm->weight = $weight++;
            }

            $spm->is_queued = true;
        }

        $playlist->queue_reset_at = $now ?? Time::nowUtc();
    }

    public function isQueueEmpty(StationPlaylist $playlist): bool
    {
        if (
            PlaylistSources::Songs !== $playlist->source
            || PlaylistOrders::Random === $playlist->order
        ) {
            return false;
        }

        foreach ($playlist->media_items as $spm) {
            if ($spm->is_queued) {
                return false;
            }
        }

        return true;
    }

    public function isQueueCompletelyFilled(StationPlaylist $playlist): bool
    {
        if (
            PlaylistSources::Songs !== $playlist->source
            || PlaylistOrders::Random === $playlist->order
        ) {
            return true;
        }

        foreach ($playlist->media_items as $spm) {
            if (!$spm->is_queued) {
                return false;
            }
        }

        return true;
    }

    public function isMediaInPlaylist(StationMedia $media, StationPlaylist $playlist): bool
    {
        if (PlaylistSources::Songs === $playlist->source) {
            foreach ($playlist->media_items as $spm) {
                if ($spm->media === $media) {
                    return true;
                }
            }

            return false;
        }

        if (PlaylistSources::Playlists === $playlist->source) {
            foreach ($playlist->playlists as $membership) {
                if ($this->isMediaInPlaylist($media, $membership->playlist)) {
                    return true;
                }
            }
        }

        return false;
    }

    // StationPlaylistRepository

    /**
     * @return StationPlaylistGroup[]
     */
    public function getPlaylistGroupQueue(StationPlaylist $playlist): array
    {
        /** @var StationPlaylistGroup[] $members */
        $members = $playlist->playlists->toArray();

        if (PlaylistOrders::Random === $playlist->order) {
            shuffle($members);

            return $members;
        }

        $members = array_values(
            array_filter($members, static fn(StationPlaylistGroup $spg): bool => $spg->is_queued)
        );

        usort(
            $members,
            static fn(StationPlaylistGroup $a, StationPlaylistGroup $b): int => $a->weight <=> $b->weight
        );

        return $members;
    }

    public function resetPlaylistGroupQueue(StationPlaylist $playlist, ?CarbonImmutable $now = null): void
    {
        /** @var StationPlaylistGroup[] $members */
        $members = $playlist->playlists->toArray();

        $isShuffle = PlaylistOrders::Shuffle === $playlist->order;
        if ($isShuffle) {
            shuffle($members);
        }

        $weight = 1;
        foreach ($members as $spg) {
            if ($isShuffle) {
                $spg->weight = $weight++;
            }

            $spg->is_queued = true;
            $spg->consecutive_plays_count = 0;
        }

        $playlist->queue_reset_at = $now ?? Time::nowUtc();
    }

    public function isPlaylistGroupQueueEmpty(StationPlaylist $playlist): bool
    {
        if (
            PlaylistSources::Playlists !== $playlist->source
            || PlaylistOrders::Random === $playlist->order
        ) {
            return false;
        }

        foreach ($playlist->playlists as $spg) {
            if ($spg->is_queued) {
                return false;
            }
        }

        return true;
    }

    public function isPlaylistGroupQueueCompletelyFilled(StationPlaylist $playlist): bool
    {
        if (
            PlaylistSources::Playlists !== $playlist->source
            || PlaylistOrders::Random === $playlist->order
        ) {
            return true;
        }

        foreach ($playlist->playlists as $spg) {
            if (!$spg->is_queued) {
                return false;
            }
        }

        return true;
    }

    // StationQueueRepository

    /**
     * @return list<array{
     *     song_id: string,
     *     timestamp_played: int,
     *     title: ?string,
     *     artist: ?string
     * }>
     */
    public function getRecentlyPlayedByTimeRange(DateTimeImmutable $now, int $minutes): array
    {
        $threshold = CarbonImmutable::instance($now)->subMinutes($minutes)->getTimestamp();

        $result = [];
        foreach ($this->history() as $entry) {
            if ($entry['timestamp_played'] < $threshold) {
                continue;
            }

            $result[] = [
                'song_id' => $entry['song_id'],
                'timestamp_played' => $entry['timestamp_played'],
                'title' => $entry['title'],
                'artist' => $entry['artist'],
            ];
        }

        return $result;
    }

    public function isPlaylistRecentlyPlayed(StationPlaylist $playlist, ?int $playPerSongs = null): bool
    {
        $playPerSongs ??= $playlist->play_per_songs;
        if ($playPerSongs <= 0) {
            return false;
        }

        $ref = $this->entities->refForPlaylist($playlist);

        $candidates = array_values(array_filter(
            $this->history(),
            static fn(array $entry): bool => $entry['is_visible'] || $entry['playlist_ref'] === $ref
        ));

        $candidates = array_slice($candidates, 0, $playPerSongs);

        foreach ($candidates as $entry) {
            if ($ref === null) {
                continue;
            }

            if ($entry['playlist_ref'] === $ref) {
                return true;
            }
        }

        return false;
    }

    public function hasCuedPlaylistMedia(StationPlaylist $playlist): bool
    {
        return $this->isCued($playlist);
    }

    public function hasCuedPlaylistGroupMedia(StationPlaylist $playlist): bool
    {
        return $this->isCued($playlist);
    }

    private function isCued(StationPlaylist $playlist): bool
    {
        $ref = $this->entities->refForPlaylist($playlist);
        if ($ref === null) {
            return false;
        }

        $cued = $this->entities->runtime->cuedPlaylists;

        return in_array($ref, $cued, true);
    }

    private function toPlaylistQueue(StationPlaylistMedia $spm): StationPlaylistQueue
    {
        $record = new StationPlaylistQueue();
        $record->spm_id = $spm->id;
        $record->media_id = $spm->media->id;
        $record->song_id = $spm->media->song_id;
        $record->artist = $spm->media->artist ?? '';
        $record->title = $spm->media->title ?? '';
        $record->last_played = $spm->last_played;

        return $record;
    }

    /**
     * @return list<HistoryEntryShape> Sorted by timestamp descending
     */
    private function history(): array
    {
        if ($this->historyCache !== null) {
            return $this->historyCache;
        }

        $entries = [];
        foreach ($this->entities->runtime->queueHistory as $row) {
            $media = $this->entities->mediaByRef[$row->mediaRef ?? ''] ?? null;

            $entries[] = [
                'song_id' => $media->song_id ?? ($row->songId ?? ''),
                'text' => $media?->text,
                'artist' => $media->artist ?? $row->artist,
                'title' => $media->title ?? $row->title,
                'timestamp_played' => $row->timestampPlayed,
                'playlist_ref' => $row->playlistRef,
                'is_visible' => $row->isVisible,
            ];
        }

        usort($entries, static fn(array $a, array $b): int => $b['timestamp_played'] <=> $a['timestamp_played']);

        return $this->historyCache = $entries;
    }
}
