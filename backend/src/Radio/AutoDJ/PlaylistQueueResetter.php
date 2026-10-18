<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Repository\StationPlaylistMediaRepository;
use App\Entity\Repository\StationPlaylistRepository;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistGroup;
use App\Utilities\Time;
use Carbon\CarbonImmutable;

final class PlaylistQueueResetter
{
    public function __construct(
        private readonly StationPlaylistRepository $spRepo,
        private readonly StationPlaylistMediaRepository $spmRepo,
    ) {
    }

    public function resetAllQueues(Station $station): void
    {
        $now = Time::nowUtc();
        $resetSequential = $station->backend_config->reset_sequential_queues_on_restart;

        foreach ($station->playlists as $playlist) {
            if (
                $playlist->preserve_queue_on_restart
                || (!$resetSequential && $playlist->order === PlaylistOrders::Sequential)
            ) {
                continue;
            }

            $this->resetPlaylistQueue($playlist, $now);
        }
    }

    /**
     * Member playlists that have preserve_queue_on_recursive_reset on any of their memberships
     * will not be reset even if other memberships under this group don't have it set.
     *
     * They share an internal queue on the playlist so resetting one while keeping the other
     * doesn't make any sense.
     */
    public function resetPlaylistGroupAndNestedMembers(
        StationPlaylist $playlistGroup,
        CarbonImmutable $resetAt
    ): void {
        $nestedMemberships = $this->getNestedMemberships($playlistGroup);
        $preservedPlaylistIds = $this->getPreservedPlaylistIds($nestedMemberships);

        $this->spRepo->resetPlaylistGroupQueue($playlistGroup, $resetAt);

        foreach ($this->getMembersById($nestedMemberships) as $memberId => $member) {
            if (!isset($preservedPlaylistIds[$memberId])) {
                $this->resetPlaylistQueue($member, $resetAt);
            }
        }
    }

    /**
     * @param list<StationPlaylistGroup> $nestedMemberships
     *
     * @return array<int, true>
     */
    private function getPreservedPlaylistIds(array $nestedMemberships): array
    {
        $preservedPlaylistIds = [];

        foreach ($nestedMemberships as $membership) {
            if (!$membership->preserve_queue_on_recursive_reset) {
                continue;
            }

            $preservedPlaylistIds[$membership->playlist_id] = true;

            if ($membership->playlist->source !== PlaylistSources::Playlists) {
                continue;
            }

            foreach ($this->getNestedMemberships($membership->playlist) as $nestedMembership) {
                $preservedPlaylistIds[$nestedMembership->playlist_id] = true;
            }
        }

        return $preservedPlaylistIds;
    }

    /**
     * @return list<StationPlaylistGroup>
     */
    private function getNestedMemberships(StationPlaylist $playlistGroup): array
    {
        $nestedMemberships = [];
        $visitedGroupIds = [$playlistGroup->id => true];
        $pendingGroups = [$playlistGroup];

        while ($pendingGroups !== []) {
            $group = array_shift($pendingGroups);

            foreach ($group->playlists as $membership) {
                $nestedMemberships[] = $membership;
                $member = $membership->playlist;

                if (
                    $member->source !== PlaylistSources::Playlists
                    || isset($visitedGroupIds[$member->id])
                ) {
                    continue;
                }

                $visitedGroupIds[$member->id] = true;
                $pendingGroups[] = $member;
            }
        }

        return $nestedMemberships;
    }

    /**
     * @param list<StationPlaylistGroup> $memberships
     *
     * @return array<int, StationPlaylist>
     */
    private function getMembersById(array $memberships): array
    {
        $membersById = [];

        foreach ($memberships as $membership) {
            $membersById[$membership->playlist_id] = $membership->playlist;
        }

        return $membersById;
    }

    private function resetPlaylistQueue(StationPlaylist $playlist, CarbonImmutable $resetAt): void
    {
        match ($playlist->source) {
            PlaylistSources::Songs => $this->spmRepo->resetQueue($playlist, $resetAt),
            PlaylistSources::Playlists => $this->spRepo->resetPlaylistGroupQueue($playlist, $resetAt),
            default => null,
        };
    }
}
