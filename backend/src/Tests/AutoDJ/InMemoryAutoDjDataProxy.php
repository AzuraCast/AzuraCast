<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\Api\StationPlaylistQueue;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistGroup;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use App\Radio\AutoDJ\DuplicatePrevention;
use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use InvalidArgumentException;
use WeakMap;

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
 * @phpstan-type QueueRowShape array{
 *     song_id: string,
 *     artist: ?string,
 *     title: ?string,
 *     playlist_ref: ?string,
 *     is_visible: bool,
 *     is_played: bool,
 *     timestamp_played: ?int
 * }
 */
final class InMemoryAutoDjDataProxy
{
    /** @var ?list<HistoryEntryShape> */
    private ?array $historyCache = null;

    /** @var list<QueueRowShape> */
    private array $queueRows = [];

    /** @var WeakMap<StationQueue, int> */
    private WeakMap $queueRowPositions;

    private readonly InMemoryCommittedState $committedState;

    public function __construct(
        private readonly InMemoryEntityStore $entities,
        private readonly DuplicatePrevention $duplicatePrevention
    ) {
        $this->committedState = new InMemoryCommittedState();
        $this->committedState->trackGroupMembers($entities->groupMembersById);
        $this->committedState->trackPlaylistMedia($entities->spmById);
        $this->committedState->trackRequests($entities->requests);

        $this->queueRowPositions = new WeakMap();

        foreach ($entities->runtime->cuedMedia as $cuedMediaEntry) {
            $media = $entities->mediaByRef[$cuedMediaEntry->mediaRef] ?? null;
            $playlist = $entities->playlistsByRef[$cuedMediaEntry->playlistRef] ?? null;
            if ($media === null || $playlist === null) {
                continue;
            }

            $queueRow = StationQueue::fromMedia($entities->station, $media);
            $queueRow->playlist = $playlist;

            $this->commitQueueRow($queueRow);
        }
    }

    // EntityManager

    /**
     * Only new queue rows need registering, managed entities are committed on flush regardless
     */
    public function persist(object $entity): void
    {
        if ($entity instanceof StationQueue) {
            $this->committedState->persistQueueEntry($entity);
        }
    }

    public function flush(): void
    {
        foreach ($this->committedState->flush() as $queueRow) {
            $this->commitQueueRow($queueRow);
        }
    }

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
                array_filter(
                    $items,
                    fn(StationPlaylistMedia $spm): bool => $this->committedState->playlistMediaRow($spm)['is_queued']
                )
            );

            usort(
                $items,
                fn(StationPlaylistMedia $a, StationPlaylistMedia $b): int
                    => $this->committedState->playlistMediaRow($a)['weight']
                        <=> $this->committedState->playlistMediaRow($b)['weight']
            );
        }

        return array_map(fn(StationPlaylistMedia $spm): StationPlaylistQueue => $this->toPlaylistQueue($spm), $items);
    }

    public function resetQueue(StationPlaylist $playlist, ?CarbonImmutable $now = null): void
    {
        if ($playlist->source !== PlaylistSources::Songs) {
            throw new InvalidArgumentException('Playlist must contain songs.');
        }

        /** @var StationPlaylistMedia[] $items */
        $items = $playlist->media_items->toArray();

        if ($playlist->order === PlaylistOrders::Sequential) {
            foreach ($items as $spm) {
                $this->committedState->markPlaylistMediaQueued($spm);
            }
        } elseif ($playlist->order === PlaylistOrders::Shuffle) {
            shuffle($items);

            $weight = 1;
            foreach ($items as $spm) {
                $this->committedState->markPlaylistMediaQueued($spm, $weight++);
            }
        }

        $this->committedState->resyncManagedEntities(
            StationPlaylistMedia::class,
            static fn(StationPlaylistMedia $spm): bool => $spm->playlist === $playlist
        );

        $now ??= Time::nowUtc();

        $playlist->queue_reset_at = $now;
        $this->persist($playlist);
        $this->flush();
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
            if ($this->committedState->playlistMediaRow($spm)['is_queued']) {
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
            if (!$this->committedState->playlistMediaRow($spm)['is_queued']) {
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
        $members = array_values(
            array_filter(
                $playlist->playlists->toArray(),
                static fn(StationPlaylistGroup $spg): bool => $spg->playlist->is_enabled
            )
        );

        if (PlaylistOrders::Random === $playlist->order) {
            shuffle($members);

            return $members;
        }

        $members = array_values(
            array_filter(
                $members,
                fn(StationPlaylistGroup $spg): bool => $this->committedState->groupMemberRow($spg)['is_queued']
            )
        );

        usort(
            $members,
            fn(StationPlaylistGroup $a, StationPlaylistGroup $b): int
                => $this->committedState->groupMemberRow($a)['weight']
                    <=> $this->committedState->groupMemberRow($b)['weight']
        );

        return $members;
    }

    public function resetPlaylistGroupQueue(StationPlaylist $playlist, ?CarbonImmutable $now = null): void
    {
        if ($playlist->source !== PlaylistSources::Playlists) {
            throw new InvalidArgumentException('Playlist must contain playlists.');
        }

        /** @var StationPlaylistGroup[] $members */
        $members = $playlist->playlists->toArray();

        if ($playlist->order === PlaylistOrders::Sequential) {
            foreach ($members as $spg) {
                $this->committedState->markGroupMemberQueued($spg);
            }
        } elseif ($playlist->order === PlaylistOrders::Shuffle) {
            shuffle($members);

            $weight = 1;
            foreach ($members as $spg) {
                $this->committedState->markGroupMemberQueued($spg, $weight++);
            }
        }

        $this->committedState->resyncManagedEntities(
            StationPlaylistGroup::class,
            static fn(StationPlaylistGroup $spg): bool => $spg->playlist_group === $playlist
        );

        $now ??= Time::nowUtc();

        $playlist->queue_reset_at = $now;
        $this->persist($playlist);
        $this->flush();
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
            if (!$spg->playlist->is_enabled) {
                continue;
            }

            if ($this->committedState->groupMemberRow($spg)['is_queued']) {
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
            if (!$spg->playlist->is_enabled) {
                continue;
            }

            if (!$this->committedState->groupMemberRow($spg)['is_queued']) {
                return false;
            }
        }

        return true;
    }

    // StationQueueRepository

    /**
     * @return list<array{
     *     song_id: string,
     *     timestamp_played: ?int,
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

        foreach (array_reverse($this->queueRows) as $row) {
            if (
                $row['is_played']
                && (
                    $row['timestamp_played'] === null
                    || $row['timestamp_played'] < $threshold
                )
            ) {
                continue;
            }

            $result[] = [
                'song_id' => $row['song_id'],
                'timestamp_played' => $row['timestamp_played'],
                'title' => $row['title'],
                'artist' => $row['artist'],
            ];
        }

        return $result;
    }

    /**
     * Has the same effect as running StationQueueRepository::trackPlayed at the start of every row.
     * Rows without a play time (seeded cued media) are never marked played.
     */
    public function markQueueRowsPlayedUntil(DateTimeImmutable $time): void
    {
        $timestamp = $time->getTimestamp();

        foreach ($this->queueRows as $position => $row) {
            if (
                $row['is_played']
                || $row['timestamp_played'] === null
                || $row['timestamp_played'] > $timestamp
            ) {
                continue;
            }

            $this->queueRows[$position]['is_played'] = true;
        }
    }

    public function isPlaylistRecentlyPlayed(StationPlaylist $playlist, ?int $playPerSongs = null): bool
    {
        $playPerSongs ??= $playlist->play_per_songs;
        if ($playPerSongs <= 0) {
            return false;
        }

        $refs = $this->playlistAndNestedMemberRefs($playlist);
        if ($refs === []) {
            return false;
        }

        $rows = [
            ...array_reverse($this->queueRows),
            ...$this->history(),
        ];

        $candidates = array_values(array_filter(
            $rows,
            static fn(array $entry): bool => (
                $entry['is_visible']
                || in_array($entry['playlist_ref'], $refs, true)
            )
        ));

        $candidates = array_slice($candidates, 0, $playPerSongs);

        foreach ($candidates as $entry) {
            if (in_array($entry['playlist_ref'], $refs, true)) {
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
        if ($this->isCued($playlist)) {
            return true;
        }

        foreach ($playlist->playlists as $membership) {
            $child = $membership->playlist;

            $hasChildCuedMedia = (PlaylistSources::Playlists === $child->source)
                ? $this->hasCuedPlaylistGroupMedia($child)
                : $this->hasCuedPlaylistMedia($child);

            if ($hasChildCuedMedia) {
                return true;
            }
        }

        return false;
    }

    // StationRequestRepository

    /**
     * @param mixed[] $additionalSongHistory
     *
     * @return list<StationRequest>
     */
    public function getPlayableRequests(
        Station $station,
        ?DateTimeImmutable $now = null,
        array $additionalSongHistory = []
    ): array {
        $now ??= Time::nowUtc();

        $unplayed = array_filter(
            $this->entities->requests,
            fn(StationRequest $request): bool => $this->committedState->requestPlayedAt($request) === null
        );

        usort(
            $unplayed,
            static fn(StationRequest $a, StationRequest $b): int
                => [$b->skip_delay, $a->id] <=> [$a->skip_delay, $b->id]
        );

        return array_values(array_filter(
            $unplayed,
            fn(StationRequest $request): bool => $request->shouldPlayNow($now)
                && !$this->hasRequestTrackPlayedRecently($request->track, $now)
                && !$this->isDuplicateOfPlayedTrack($request->track, $additionalSongHistory)
        ));
    }

    /**
     * @param mixed[] $additionalSongHistory
     */
    public function getNextPlayableRequest(
        Station $station,
        ?DateTimeImmutable $now = null,
        array $additionalSongHistory = []
    ): ?StationRequest {
        return $this->getPlayableRequests($station, $now, $additionalSongHistory)[0] ?? null;
    }

    // Internal helpers

    /**
     * Inserts the row or overwrites its committed copy when the same row is flushed again
     */
    private function commitQueueRow(StationQueue $queueRow): void
    {
        $playlist = $queueRow->playlist;

        $row = [
            'song_id' => $queueRow->song_id,
            'artist' => $queueRow->artist,
            'title' => $queueRow->title,
            'playlist_ref' => ($playlist !== null) ? $this->entities->refForPlaylist($playlist) : null,
            'is_visible' => $queueRow->is_visible,
            'is_played' => $queueRow->is_played,
            'timestamp_played' => $queueRow->timestamp_played?->getTimestamp(),
        ];

        $position = $this->queueRowPositions[$queueRow] ?? null;
        if ($position === null) {
            $this->queueRowPositions[$queueRow] = count($this->queueRows);
            $this->queueRows[] = $row;

            return;
        }

        $this->queueRows[$position] = $row;
    }

    private function isCued(StationPlaylist $playlist): bool
    {
        $ref = $this->entities->refForPlaylist($playlist);
        if ($ref === null) {
            return false;
        }

        foreach ($this->queueRows as $row) {
            if (!$row['is_played'] && $row['playlist_ref'] === $ref) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param int[] $visitedIds
     *
     * @return string[]
     */
    private function playlistAndNestedMemberRefs(StationPlaylist $playlist, array $visitedIds = []): array
    {
        if (in_array($playlist->id, $visitedIds, true)) {
            return [];
        }

        $visitedIds[] = $playlist->id;

        $refs = [];

        $ref = $this->entities->refForPlaylist($playlist);
        if ($ref !== null) {
            $refs[] = $ref;
        }

        if ($playlist->source !== PlaylistSources::Playlists) {
            return $refs;
        }

        foreach ($playlist->playlists as $membership) {
            $refs = [
                ...$refs,
                ...$this->playlistAndNestedMemberRefs($membership->playlist, $visitedIds),
            ];
        }

        return array_values(array_unique($refs));
    }

    private function toPlaylistQueue(StationPlaylistMedia $spm): StationPlaylistQueue
    {
        $record = new StationPlaylistQueue();
        $record->spm_id = $spm->id;
        $record->media_id = $spm->media->id;
        $record->song_id = $spm->media->song_id;
        $record->artist = $spm->media->artist ?? '';
        $record->title = $spm->media->title ?? '';
        $record->last_played = $this->committedState->playlistMediaRow($spm)['last_played'];

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

    private function hasRequestTrackPlayedRecently(StationMedia $media, DateTimeImmutable $now): bool
    {
        $thresholdMins = $this->entities->station->request_threshold ?? 15;
        if ($thresholdMins === 0) {
            return false;
        }

        $threshold = CarbonImmutable::instance($now)->subMinutes($thresholdMins)->getTimestamp();

        $recentTracks = [];
        foreach ($this->history() as $entry) {
            if ($entry['timestamp_played'] < $threshold) {
                continue;
            }

            $recentTracks[] = [
                'song_id' => $entry['song_id'],
                'text' => $entry['text'],
                'artist' => $entry['artist'],
                'title' => $entry['title'],
                'timestamp_played' => $entry['timestamp_played'],
            ];
        }

        return $this->isDuplicateOfPlayedTrack($media, $recentTracks);
    }

    /**
     * @param mixed[] $playedTracks
     */
    private function isDuplicateOfPlayedTrack(StationMedia $media, array $playedTracks): bool
    {
        if ($playedTracks === []) {
            return false;
        }

        $eligibleTrack = new StationPlaylistQueue();
        $eligibleTrack->media_id = $media->id;
        $eligibleTrack->song_id = $media->song_id;
        $eligibleTrack->title = $media->title ?? '';
        $eligibleTrack->artist = $media->artist ?? '';

        return $this->duplicatePrevention->getDistinctTrack([$eligibleTrack], $playedTracks) === null;
    }
}
