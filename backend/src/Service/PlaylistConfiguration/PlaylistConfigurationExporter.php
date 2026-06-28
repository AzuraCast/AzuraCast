<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration;

use App\Entity\Enums\PlaylistSources;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Service\PlaylistConfiguration\Schema\MediaEntry;
use App\Service\PlaylistConfiguration\Schema\PlaylistConfigurationSchema;
use App\Service\PlaylistConfiguration\Schema\PlaylistEntry;
use App\Service\PlaylistConfiguration\Schema\PlaylistFolderEntry;
use App\Service\PlaylistConfiguration\Schema\PlaylistMediaEntry;
use App\Service\PlaylistConfiguration\Schema\PlaylistMemberEntry;
use App\Service\PlaylistConfiguration\Schema\PlaylistScheduleEntry;

final class PlaylistConfigurationExporter
{
    public function exportStationPlaylists(Station $station): PlaylistConfigurationSchema
    {
        return $this->build(
            $station,
            $station->playlists->toArray(),
            PlaylistConfigurationType::STATION
        );
    }

    public function exportPlaylist(StationPlaylist $playlist): PlaylistConfigurationSchema
    {
        $collectedPlaylists = [];
        $this->collectPlaylistAndMembers($playlist, $collectedPlaylists);

        return $this->build(
            $playlist->station,
            array_values($collectedPlaylists),
            PlaylistConfigurationType::PLAYLIST
        );
    }

    /**
     * @param array<int, StationPlaylist> $collectedPlaylists Keyed by playlist id
     */
    private function collectPlaylistAndMembers(
        StationPlaylist $playlist,
        array &$collectedPlaylists
    ): void {
        $id = $playlist->id;
        if (isset($collectedPlaylists[$id])) {
            return;
        }

        $collectedPlaylists[$id] = $playlist;

        if (PlaylistSources::Playlists === $playlist->source) {
            foreach ($playlist->playlists as $member) {
                $this->collectPlaylistAndMembers($member->playlist, $collectedPlaylists);
            }
        }
    }

    /**
     * @param StationPlaylist[] $playlists
     */
    private function build(
        Station $station,
        array $playlists,
        PlaylistConfigurationType $type
    ): PlaylistConfigurationSchema {
        $playlistConfigurationSchema = new PlaylistConfigurationSchema(
            type: $type,
            station: $station,
            mediaEntries: [],
            playlistEntries: [],
        );

        $playlistRefs = [];
        foreach ($playlists as $playlist) {
            $playlistRefs[$playlist->id] = $this->uniqueRef(
                StationPlaylist::generateShortName($playlist->name),
                $playlistRefs
            );
        }

        foreach ($playlists as $playlist) {
            $this->exportPlaylistEntry(
                $playlistConfigurationSchema,
                $playlist,
                $playlistRefs,
            );
        }

        return $playlistConfigurationSchema;
    }

    /**
     * @param array<int, string> $playlistRefs Keyed by playlist id
     */
    private function exportPlaylistEntry(
        PlaylistConfigurationSchema $playlistConfigurationSchema,
        StationPlaylist $playlist,
        array $playlistRefs,
    ): void {
        $playlistEntry = new PlaylistEntry(
            ref: $playlistRefs[$playlist->id],
            name: $playlist->name,
            type: $playlist->type,
            source: $playlist->source,
            order: $playlist->order,
            weight: $playlist->weight,
            isEnabled: $playlist->is_enabled,
            isJingle: $playlist->is_jingle,
            avoidDuplicates: $playlist->avoid_duplicates,
            includeInRequests: $playlist->include_in_requests,
            includeInOnDemand: $playlist->include_in_on_demand,
            playPerSongs: $playlist->play_per_songs,
            playPerMinutes: $playlist->play_per_minutes,
            playPerHourMinute: $playlist->play_per_hour_minute,
            backendOptions: $playlist->backend_options,
            remoteUrl: $playlist->remote_url,
            remoteType: $playlist->remote_type,
            remoteBuffer: $playlist->remote_buffer,
            description: $playlist->description,
            folders: [],
            media: [],
            schedules: [],
            members: [],
        );

        $folderRefById = [];
        foreach ($playlist->folders as $index => $folder) {
            $folderRef = "f{$index}";
            $folderRefById[$folder->id] = $folderRef;

            $playlistEntry->folders[] = new PlaylistFolderEntry(
                ref: $folderRef,
                path: $folder->path,
            );
        }

        foreach ($playlist->media_items as $playlistMedia) {
            $mediaId = $playlistMedia->media->id;

            if (!isset($playlistConfigurationSchema->mediaEntries[$mediaId])) {
                $playlistConfigurationSchema->mediaEntries[$mediaId] = new MediaEntry(
                    ref: 'm' . count($playlistConfigurationSchema->mediaEntries),
                    path: $playlistMedia->media->path,
                    uniqueId: $playlistMedia->media->unique_id,
                    length: $playlistMedia->media->length,
                    artist: $playlistMedia->media->artist,
                    title: $playlistMedia->media->title,
                    album: $playlistMedia->media->album,
                    genre: $playlistMedia->media->genre,
                );
            }

            $folder = $playlistMedia->folder;

            $playlistEntry->media[] = new PlaylistMediaEntry(
                mediaRef: $playlistConfigurationSchema->mediaEntries[$mediaId]->ref,
                weight: $playlistMedia->weight,
                folderRef: ($folder !== null) ? ($folderRefById[$folder->id] ?? null) : null,
            );
        }

        foreach ($playlist->schedule_items as $schedule) {
            $playlistEntry->schedules[] = new PlaylistScheduleEntry(
                startTime: $schedule->start_time,
                endTime: $schedule->end_time,
                days: $schedule->days,
                startDate: $schedule->start_date,
                endDate: $schedule->end_date,
                loopOnce: $schedule->loop_once,
                preventRequests: $schedule->prevent_requests,
            );
        }

        foreach ($playlist->playlists as $playlistGroup) {
            $memberRef = $playlistRefs[$playlistGroup->playlist->id] ?? null;
            if ($memberRef === null) {
                continue;
            }

            $playlistEntry->members[] = new PlaylistMemberEntry(
                playlistRef: $memberRef,
                weight: $playlistGroup->weight,
                consecutivePlays: $playlistGroup->consecutive_plays,
                allowedRequests: $playlistGroup->allowed_requests,
            );
        }

        $playlistConfigurationSchema->playlistEntries[] = $playlistEntry;
    }

    /**
     * @param string[] $existingRefs
     */
    private function uniqueRef(string $base, array $existingRefs): string
    {
        $base = ('' !== $base) ? $base : 'playlist';

        $ref = $base;
        $suffix = 2;
        while (in_array($ref, $existingRefs, true)) {
            $ref = $base . '_' . $suffix;
            $suffix++;
        }

        return $ref;
    }
}
