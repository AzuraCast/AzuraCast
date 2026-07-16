<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\Enums\PlaylistGroupAllowedRequests;
use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Entity\Enums\StorageLocationAdapters;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistFolder;
use App\Entity\StationPlaylistGroup;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationRequest;
use App\Entity\StationSchedule;
use App\Entity\StorageLocation;
use App\Tests\AutoDJ\Scenario\ScenarioRuntime;
use App\Utilities\Time;
use App\Utilities\Types;
use Carbon\CarbonImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use ReflectionProperty;

/**
 * Builds an InMemoryEntityStore of entities with fake ids from a playlist
 * configuration dump with a scenarios runtime overrides applied on top
 *
 * @phpstan-type PlaylistDataByRefIndex list<array{
 *     ref: string,
 *     data: array<string, mixed>
 * }>
 */
final class InMemoryEntityHydrator
{
    private int $mediaIdSeq = 1;
    private int $spmIdSeq = 1;
    private int $playlistIdSeq = 1;
    private int $folderIdSeq = 1;
    private int $scheduleIdSeq = 1;
    private int $groupIdSeq = 1;
    private int $requestIdSeq = 1;

    /**
     * @param array<string, mixed> $dump
     */
    public function hydrate(array $dump, ScenarioRuntime $runtime): InMemoryEntityStore
    {
        /** @var array<string, mixed> $stationData */
        $stationData = Types::array($dump['station'] ?? []);

        $station = new Station();
        self::setId($station, 1);
        $station->timezone = Types::stringOrNull($stationData['timezone'] ?? null) ?? $station->timezone;
        $station->requests_only_via_playlists = Types::bool(
            $stationData['requests_only_via_playlists'] ?? $station->requests_only_via_playlists
        );
        $station->request_delay = Types::intOrNull($stationData['request_delay'] ?? null) ?? $station->request_delay;
        $station->request_threshold = Types::intOrNull($stationData['request_threshold'] ?? null)
            ?? $station->request_threshold;

        $storageLocation = new StorageLocation(StorageLocationTypes::StationMedia, StorageLocationAdapters::Local);
        self::setId($storageLocation, 1);

        [
            'mediaByRef' => $mediaByRef,
            'mediaById' => $mediaById,
        ] = $this->hydrateMedia($storageLocation, $dump);

        [
            'playlistsByRef' => $playlistsByRef,
            'refByPlaylistId' => $refByPlaylistId,
            'playlistDataByRefIndex' => $playlistDataByRefIndex,
        ] = $this->hydratePlaylists($station, $dump);

        [
            'spmById' => $spmById,
            'spmByRefPair' => $spmByRefPair,
        ] = $this->hydratePlaylistContents(
            station: $station,
            playlistDataByRefIndex: $playlistDataByRefIndex,
            playlistsByRef: $playlistsByRef,
            mediaByRef: $mediaByRef
        );

        $spgByRefPair = $this->hydrateGroupMemberships($playlistDataByRefIndex, $playlistsByRef);

        self::setCollection(
            $station,
            'playlists',
            new ArrayCollection(array_values($playlistsByRef))
        );

        $this->applyRuntime(
            runtime: $runtime,
            playlistsByRef: $playlistsByRef,
            spmByRefPair: $spmByRefPair,
            spgByRefPair: $spgByRefPair
        );

        $requests = $this->hydrateRequests($station, $runtime, $mediaByRef);

        return new InMemoryEntityStore(
            $station,
            $playlistsByRef,
            $mediaByRef,
            $mediaById,
            $spmById,
            $refByPlaylistId,
            $runtime,
            $requests
        );
    }

    private static function setId(object $entity, int $id): void
    {
        new ReflectionProperty($entity, 'id')
            ->setValue($entity, $id);
    }

    private static function setCollection(
        object $entity,
        string $property,
        ArrayCollection $collection
    ): void {
        new ReflectionProperty($entity, $property)
            ->setValue($entity, $collection);
    }

    /**
     * @param array<string, mixed> $dump
     *
     * @return array{
     *     mediaByRef: array<string, StationMedia>,
     *     mediaById: array<int, StationMedia>
     * }
     */
    private function hydrateMedia(StorageLocation $storageLocation, array $dump): array
    {
        $mediaByRef = [];
        $mediaById = [];
        foreach (Types::array($dump['media'] ?? []) as $mediaData) {
            $mediaData = Types::array($mediaData);

            $media = new StationMedia($storageLocation, Types::string($mediaData['path'] ?? ''));
            $media->artist = Types::stringOrNull($mediaData['artist'] ?? null);
            $media->title = Types::stringOrNull($mediaData['title'] ?? null);
            $media->album = Types::stringOrNull($mediaData['album'] ?? null);
            $media->genre = Types::stringOrNull($mediaData['genre'] ?? null);
            $media->length = Types::float($mediaData['length'] ?? 0.0);
            $media->text = null;
            $media->updateMetaFields();

            $id = $this->mediaIdSeq++;
            self::setId($media, $id);

            $ref = Types::string($mediaData['ref'] ?? ('m' . $id));

            $mediaByRef[$ref] = $media;
            $mediaById[$id] = $media;
        }

        return [
            'mediaByRef' => $mediaByRef,
            'mediaById' => $mediaById,
        ];
    }

    /**
     * @param array<string, mixed> $dump
     *
     * @return array{
     *     playlistsByRef: array<string, StationPlaylist>,
     *     refByPlaylistId: array<int, string>,
     *     playlistDataByRefIndex: PlaylistDataByRefIndex
     * }
     */
    private function hydratePlaylists(Station $station, array $dump): array
    {
        $playlistsByRef = [];
        $refByPlaylistId = [];
        $playlistDataByRefIndex = [];

        foreach (Types::array($dump['playlists'] ?? []) as $playlistData) {
            $playlistData = Types::array($playlistData);
            $ref = Types::string($playlistData['ref'] ?? '');

            $playlist = $this->createPlaylist($station, $playlistData);
            self::setId($playlist, $this->playlistIdSeq++);

            $playlistsByRef[$ref] = $playlist;
            $refByPlaylistId[$playlist->id] = $ref;

            $playlistDataByRefIndex[] = [
                'ref' => $ref,
                'data' => $playlistData,
            ];
        }

        return [
            'playlistsByRef' => $playlistsByRef,
            'refByPlaylistId' => $refByPlaylistId,
            'playlistDataByRefIndex' => $playlistDataByRefIndex,
        ];
    }

    /**
     * @param PlaylistDataByRefIndex $playlistDataByRefIndex
     * @param array<string, StationPlaylist> $playlistsByRef
     * @param array<string, StationMedia> $mediaByRef
     *
     * @return array{
     *     spmById: array<int, StationPlaylistMedia>,
     *     spmByRefPair: array<string, StationPlaylistMedia>
     * }
     */
    private function hydratePlaylistContents(
        Station $station,
        array $playlistDataByRefIndex,
        array $playlistsByRef,
        array $mediaByRef
    ): array {
        $spmById = [];
        $spmByRefPair = [];
        foreach ($playlistDataByRefIndex as $entry) {
            $ref = Types::string($entry['ref']);
            $playlistData = Types::array($entry['data']);
            $playlist = $playlistsByRef[$ref];

            $folderByRef = [];
            foreach (Types::array($playlistData['folders'] ?? []) as $folderData) {
                $folderData = Types::array($folderData);

                $folder = new StationPlaylistFolder(
                    $station,
                    $playlist,
                    Types::string($folderData['path'] ?? '')
                );

                self::setId($folder, $this->folderIdSeq++);
                $playlist->folders->add($folder);

                $folderRef = Types::string($folderData['ref'] ?? '');
                $folderByRef[$folderRef] = $folder;
            }

            foreach (Types::array($playlistData['media'] ?? []) as $itemData) {
                $itemData = Types::array($itemData);

                $mediaRef = Types::string($itemData['media_ref'] ?? '');
                if (!isset($mediaByRef[$mediaRef])) {
                    continue;
                }

                $folderRef = Types::stringOrNull($itemData['folder_ref'] ?? null);
                $folder = ($folderRef !== null) ? ($folderByRef[$folderRef] ?? null) : null;

                $spm = new StationPlaylistMedia($playlist, $mediaByRef[$mediaRef], $folder);
                $spm->weight = Types::int($itemData['weight'] ?? 0);

                $spmId = $this->spmIdSeq++;
                self::setId($spm, $spmId);

                $playlist->media_items->add($spm);

                $spmById[$spmId] = $spm;
                $spmByRefPair[$ref . ':' . $mediaRef] = $spm;
            }

            foreach (Types::array($playlistData['schedules'] ?? []) as $scheduleData) {
                $scheduleData = Types::array($scheduleData);

                $schedule = new StationSchedule($playlist);
                $schedule->start_time = Types::int($scheduleData['start_time'] ?? $schedule->start_time);
                $schedule->end_time = Types::int($scheduleData['end_time'] ?? $schedule->end_time);

                $schedule->days = array_map(
                    static fn($d): int => Types::int($d),
                    Types::array($scheduleData['days'] ?? [])
                );

                $schedule->start_date = Types::stringOrNull($scheduleData['start_date'] ?? $schedule->start_date);
                $schedule->end_date = Types::stringOrNull($scheduleData['end_date'] ?? $schedule->end_date);
                $schedule->loop_once = Types::bool($scheduleData['loop_once'] ?? $schedule->loop_once);
                $schedule->prevent_requests = Types::bool(
                    $scheduleData['prevent_requests'] ?? $schedule->prevent_requests
                );

                self::setId($schedule, $this->scheduleIdSeq++);

                $playlist->schedule_items->add($schedule);
            }
        }

        return [
            'spmById' => $spmById,
            'spmByRefPair' => $spmByRefPair,
        ];
    }

    /**
     * @param PlaylistDataByRefIndex $playlistDataByRefIndex
     * @param array<string, StationPlaylist> $playlistsByRef
     *
     * @return array<string, StationPlaylistGroup> Keyed by ref pair
     */
    private function hydrateGroupMemberships(
        array $playlistDataByRefIndex,
        array $playlistsByRef
    ): array {
        $spgByRefPair = [];
        foreach ($playlistDataByRefIndex as $entry) {
            $containerRef = Types::string($entry['ref']);
            $playlistData = Types::array($entry['data']);
            $container = $playlistsByRef[$containerRef];

            foreach (Types::array($playlistData['members'] ?? []) as $memberData) {
                $memberData = Types::array($memberData);
                $memberRef = Types::string($memberData['playlist_ref'] ?? '');

                if (!isset($playlistsByRef[$memberRef]) || $memberRef === $containerRef) {
                    continue;
                }

                $member = $playlistsByRef[$memberRef];

                $group = new StationPlaylistGroup($member, $container);
                $group->weight = Types::int($memberData['weight'] ?? $group->weight);

                $consecutivePlays = Types::int($memberData['consecutive_plays'] ?? $group->consecutive_plays);
                $group->consecutive_plays = max(0, $consecutivePlays);

                $group->play_full_cycle = Types::bool(
                    $memberData['play_full_cycle'] ?? $group->play_full_cycle
                );

                $group->allowed_requests = PlaylistGroupAllowedRequests::tryFrom(
                    Types::string($memberData['allowed_requests'] ?? null)
                ) ?? PlaylistGroupAllowedRequests::Any;

                self::setId($group, $this->groupIdSeq++);

                $container->playlists->add($group);
                $member->playlist_groups->add($group);

                $spgByRefPair[$containerRef . ':' . $memberRef] = $group;
            }
        }

        return $spgByRefPair;
    }

    /**
     * @param array<string, mixed> $playlistData
     */
    private function createPlaylist(Station $station, array $playlistData): StationPlaylist
    {
        /** @var array<string, mixed> $config */
        $config = Types::array($playlistData['config'] ?? []);

        $playlist = new StationPlaylist($station);
        $playlist->name = Types::stringOrNull($playlistData['name'] ?? null) ?? 'Playlist';
        $playlist->description = Types::stringOrNull($config['description'] ?? null);

        $playlist->source = PlaylistSources::from(Types::string($config['source'] ?? PlaylistSources::Songs->value));
        if (in_array($playlist->source, [PlaylistSources::Songs, PlaylistSources::Playlists], true)) {
            $playlist->type = PlaylistTypes::from(Types::string($config['type'] ?? PlaylistTypes::Standard->value));
        }

        $playlist->order = PlaylistOrders::from(Types::string($config['order'] ?? PlaylistOrders::Shuffle->value));
        $playlist->weight = Types::int($config['weight'] ?? $playlist->weight);
        $playlist->is_enabled = Types::bool($config['is_enabled'] ?? $playlist->is_enabled);
        $playlist->is_jingle = Types::bool($config['is_jingle'] ?? $playlist->is_jingle);
        $playlist->avoid_duplicates = Types::bool($config['avoid_duplicates'] ?? $playlist->avoid_duplicates);
        $playlist->include_in_requests = Types::bool($config['include_in_requests'] ?? $playlist->include_in_requests);
        $playlist->include_in_on_demand = Types::bool(
            $config['include_in_on_demand'] ?? $playlist->include_in_on_demand
        );
        $playlist->play_per_songs = Types::int($config['play_per_songs'] ?? $playlist->play_per_songs);
        $playlist->play_per_minutes = Types::int($config['play_per_minutes'] ?? $playlist->play_per_minutes);
        $playlist->play_per_hour_minute = Types::int(
            $config['play_per_hour_minute'] ?? $playlist->play_per_hour_minute
        );

        $playlist->backend_options = array_map(
            static fn($option): string => Types::string($option),
            Types::array($config['backend_options'] ?? [])
        );

        $playlist->remote_url = Types::stringOrNull($config['remote_url'] ?? $playlist->remote_url);
        $playlist->remote_type = PlaylistRemoteTypes::tryFrom(
            Types::string($config['remote_type'] ?? null)
        ) ?? $playlist->remote_type;
        $playlist->remote_buffer = Types::int($config['remote_buffer'] ?? $playlist->remote_buffer);

        return $playlist;
    }

    /**
     * @param array<string, StationMedia> $mediaByRef
     *
     * @return StationRequest[] In id order
     */
    private function hydrateRequests(
        Station $station,
        ScenarioRuntime $runtime,
        array $mediaByRef
    ): array {
        $requests = [];
        foreach ($runtime->requests as $entry) {
            if (!isset($mediaByRef[$entry->mediaRef])) {
                continue;
            }

            if ($entry->timestamp !== null) {
                $restore = CarbonImmutable::getTestNow();

                // StationRequest timestamp is readonly and set from "now" in the constructor
                CarbonImmutable::setTestNow(CarbonImmutable::createFromTimestamp($entry->timestamp, 'UTC'));
                $request = new StationRequest(
                    station: $station,
                    track: $mediaByRef[$entry->mediaRef],
                    ip: '127.0.0.1',
                    skipDelay: $entry->skipDelay
                );

                CarbonImmutable::setTestNow($restore);
            } else {
                $request = new StationRequest(
                    station: $station,
                    track: $mediaByRef[$entry->mediaRef],
                    ip: '127.0.0.1',
                    skipDelay: $entry->skipDelay
                );
            }

            self::setId($request, $this->requestIdSeq++);

            if ($entry->played) {
                $request->played_at = Time::nowUtc();
            }

            $requests[] = $request;
        }

        return $requests;
    }

    /**
     * @param array<string, StationPlaylist> $playlistsByRef
     * @param array<string, StationPlaylistMedia> $spmByRefPair
     * @param array<string, StationPlaylistGroup> $spgByRefPair
     */
    private function applyRuntime(
        ScenarioRuntime $runtime,
        array $playlistsByRef,
        array $spmByRefPair,
        array $spgByRefPair
    ): void {
        foreach ($runtime->playlists as $ref => $state) {
            if (!isset($playlistsByRef[$ref])) {
                continue;
            }

            $playlist = $playlistsByRef[$ref];

            if ($state->hasPlayedAt) {
                $playlist->played_at = $state->playedAt;
            }

            if ($state->hasQueueResetAt) {
                $playlist->queue_reset_at = $state->queueResetAt;
            }
        }

        foreach ($runtime->playlistMedia as $pair => $state) {
            if (!isset($spmByRefPair[$pair])) {
                continue;
            }

            $spm = $spmByRefPair[$pair];

            if ($state->isQueued !== null) {
                $spm->is_queued = $state->isQueued;
            }

            if ($state->lastPlayed !== null) {
                $spm->last_played = $state->lastPlayed;
            }
        }

        foreach ($runtime->groupMembers as $pair => $state) {
            if (!isset($spgByRefPair[$pair])) {
                continue;
            }

            $group = $spgByRefPair[$pair];

            if ($state->isQueued !== null) {
                $group->is_queued = $state->isQueued;
            }

            if ($state->consecutivePlaysCount !== null) {
                $group->consecutive_plays_count = $state->consecutivePlaysCount;
            }

            if ($state->lastPlayed !== null) {
                $group->last_played = $state->lastPlayed;
            }
        }
    }
}
