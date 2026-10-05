<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\StationPlaylistGroup;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Entity\StationRequest;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Committed copy of the queue columns the AutoDJ changes during a pick, updated only on flush,
 * so the fake repositories answer like queries against the database
 *
 * @phpstan-type CommittedGroupMemberRow array{
 *     is_queued: bool,
 *     weight: int,
 *     consecutive_plays_count: int,
 *     last_played: int
 * }
 * @phpstan-type CommittedPlaylistMediaRow array{
 *     is_queued: bool,
 *     weight: int,
 *     last_played: int
 * }
 */
final class InMemoryCommittedState
{
    /** @var array<int, StationPlaylistGroup> */
    private array $groupMembersById = [];

    /** @var array<int, CommittedGroupMemberRow> */
    private array $groupMemberRowsById = [];

    /** @var array<int, StationPlaylistMedia> */
    private array $playlistMediaById = [];

    /** @var array<int, CommittedPlaylistMediaRow> */
    private array $playlistMediaRowsById = [];

    /** @var array<int, StationRequest> */
    private array $requestsById = [];

    /** @var array<int, ?DateTimeImmutable> */
    private array $requestPlayedAtById = [];

    /** @var list<StationQueue> */
    private array $pendingQueueEntries = [];

    /**
     * @param iterable<StationPlaylistGroup> $members
     */
    public function trackGroupMembers(iterable $members): void
    {
        foreach ($members as $member) {
            $this->groupMembersById[$member->id] = $member;
            $this->groupMemberRowsById[$member->id] = self::groupMemberRowOf($member);
        }
    }

    /**
     * @param iterable<StationPlaylistMedia> $items
     */
    public function trackPlaylistMedia(iterable $items): void
    {
        foreach ($items as $spm) {
            $this->playlistMediaById[$spm->id] = $spm;
            $this->playlistMediaRowsById[$spm->id] = self::playlistMediaRowOf($spm);
        }
    }

    /**
     * @param iterable<StationRequest> $requests
     */
    public function trackRequests(iterable $requests): void
    {
        foreach ($requests as $request) {
            $this->requestsById[$request->id] = $request;
            $this->requestPlayedAtById[$request->id] = $request->played_at;
        }
    }

    /**
     * @return CommittedGroupMemberRow
     */
    public function groupMemberRow(StationPlaylistGroup $member): array
    {
        return $this->groupMemberRowsById[$member->id]
            ?? throw new InvalidArgumentException("Group member {$member->id} is not tracked.");
    }

    /**
     * @return CommittedPlaylistMediaRow
     */
    public function playlistMediaRow(StationPlaylistMedia $spm): array
    {
        return $this->playlistMediaRowsById[$spm->id]
            ?? throw new InvalidArgumentException("Playlist media {$spm->id} is not tracked.");
    }

    public function requestPlayedAt(StationRequest $request): ?DateTimeImmutable
    {
        if (!isset($this->requestsById[$request->id])) {
            throw new InvalidArgumentException("Request {$request->id} is not tracked.");
        }

        return $this->requestPlayedAtById[$request->id];
    }

    public function markGroupMemberQueued(StationPlaylistGroup $member, ?int $weight = null): void
    {
        $row = $this->groupMemberRow($member);
        $row['is_queued'] = true;
        $row['consecutive_plays_count'] = 0;

        if ($weight !== null) {
            $row['weight'] = $weight;
        }

        $this->groupMemberRowsById[$member->id] = $row;
    }

    public function markPlaylistMediaQueued(StationPlaylistMedia $spm, ?int $weight = null): void
    {
        $row = $this->playlistMediaRow($spm);
        $row['is_queued'] = true;

        if ($weight !== null) {
            $row['weight'] = $weight;
        }

        $this->playlistMediaRowsById[$spm->id] = $row;
    }

    /**
     * Refreshes the matching live entities from their committed rows, dropping pending changes,
     * like the HINT_REFRESH resync after a bulk DQL write does in the actual autodj code.
     *
     * @template T of IdentifiableEntityInterface
     *
     * @param class-string<T> $entityClass
     * @param callable(T): bool $matcher
     */
    public function resyncManagedEntities(string $entityClass, callable $matcher): void
    {
        $trackedEntities = match ($entityClass) {
            StationPlaylistGroup::class => $this->groupMembersById,
            StationPlaylistMedia::class => $this->playlistMediaById,
            default => throw new InvalidArgumentException("Entities of type {$entityClass} are not tracked."),
        };

        /** @var T $entity */
        foreach ($trackedEntities as $entity) {
            if (!$matcher($entity)) {
                continue;
            }

            if ($entity instanceof StationPlaylistGroup) {
                $this->refreshGroupMember($entity);
            } elseif ($entity instanceof StationPlaylistMedia) {
                $this->refreshPlaylistMedia($entity);
            }
        }
    }

    private function refreshGroupMember(StationPlaylistGroup $member): void
    {
        $row = $this->groupMemberRow($member);

        $member->is_queued = $row['is_queued'];
        $member->weight = $row['weight'];
        $member->consecutive_plays_count = $row['consecutive_plays_count'];
        $member->last_played = $row['last_played'];
    }

    private function refreshPlaylistMedia(StationPlaylistMedia $spm): void
    {
        $row = $this->playlistMediaRow($spm);

        $spm->is_queued = $row['is_queued'];
        $spm->weight = $row['weight'];
        $spm->last_played = $row['last_played'];
    }

    public function persistQueueEntry(StationQueue $entry): void
    {
        if (!in_array($entry, $this->pendingQueueEntries, true)) {
            $this->pendingQueueEntries[] = $entry;
        }
    }

    /**
     * @return list<StationQueue> Queue rows inserted by this flush
     */
    public function flush(): array
    {
        foreach ($this->groupMembersById as $id => $member) {
            $this->groupMemberRowsById[$id] = self::groupMemberRowOf($member);
        }

        foreach ($this->playlistMediaById as $id => $spm) {
            $this->playlistMediaRowsById[$id] = self::playlistMediaRowOf($spm);
        }

        foreach ($this->requestsById as $id => $request) {
            $this->requestPlayedAtById[$id] = $request->played_at;
        }

        $insertedQueueEntries = $this->pendingQueueEntries;
        $this->pendingQueueEntries = [];

        return $insertedQueueEntries;
    }

    /**
     * @return CommittedGroupMemberRow
     */
    private static function groupMemberRowOf(StationPlaylistGroup $member): array
    {
        return [
            'is_queued' => $member->is_queued,
            'weight' => $member->weight,
            'consecutive_plays_count' => $member->consecutive_plays_count,
            'last_played' => $member->last_played,
        ];
    }

    /**
     * @return CommittedPlaylistMediaRow
     */
    private static function playlistMediaRowOf(StationPlaylistMedia $spm): array
    {
        return [
            'is_queued' => $spm->is_queued,
            'weight' => $spm->weight,
            'last_played' => $spm->last_played,
        ];
    }
}
