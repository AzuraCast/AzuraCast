<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration\Schema;

use App\Entity\Station;
use App\Service\PlaylistConfiguration\PlaylistConfigurationType;
use App\Utilities\Time;
use App\Utilities\Types;
use JsonSerializable;

/**
 * @phpstan-import-type MediaEntryShape from MediaEntry
 * @phpstan-import-type PlaylistEntryShape from PlaylistEntry
 *
 * @phpstan-type StationShape array{
 *     name: string,
 *     timezone: string,
 *     requests_only_via_playlists: bool,
 *     backend_config: array{
 *         duplicate_prevention_time_range: int,
 *         autodj_queue_length: int,
 *         crossfade: float
 *     }
 * }
 *
 * @phpstan-type PlaylistConfigurationDump array{
 *     schema_version: int,
 *     type: value-of<PlaylistConfigurationType>,
 *     exported_at: string,
 *     station: StationShape,
 *     media: MediaEntryShape[],
 *     playlists: PlaylistEntryShape[]
 * }
 */
final class PlaylistConfigurationSchema implements JsonSerializable
{
    // Bumped on breaking schema changes
    public const int VERSION = 1;

    /**
     * @param MediaEntry[] $mediaEntries
     * @param PlaylistEntry[] $playlistEntries
     */
    public function __construct(
        public readonly PlaylistConfigurationType $type,
        public readonly Station $station,
        public array $mediaEntries,
        public array $playlistEntries,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data, Station $station): self
    {
        return new self(
            type: PlaylistConfigurationType::from(Types::string($data['type'] ?? null)),
            station: $station,
            mediaEntries: array_map(
                static fn(mixed $item): MediaEntry => MediaEntry::fromArray(Types::array($item)),
                Types::array($data['media'] ?? [])
            ),
            playlistEntries: array_map(
                static fn(mixed $item): PlaylistEntry => PlaylistEntry::fromArray(Types::array($item)),
                Types::array($data['playlists'] ?? [])
            ),
        );
    }

    /**
     * @see PlaylistConfigurationDump for full serialized format since PHPStan can't infer the JSON serialized types
     *
     * @return array{
     *     schema_version: int,
     *     type: PlaylistConfigurationType,
     *     exported_at: string,
     *     station: StationShape,
     *     media: MediaEntry[],
     *     playlists: PlaylistEntry[]
     * }
     */
    public function jsonSerialize(): mixed
    {
        $backendConfig = $this->station->backend_config;

        return [
            'schema_version' => PlaylistConfigurationSchema::VERSION,
            'type' => $this->type,
            'exported_at' => Time::nowUtc()->toIso8601String(),
            'station' => [
                'name' => $this->station->name,
                'timezone' => $this->station->timezone,
                'requests_only_via_playlists' => $this->station->requests_only_via_playlists,
                'backend_config' => [
                    'duplicate_prevention_time_range' => $backendConfig->duplicate_prevention_time_range,
                    'autodj_queue_length' => $backendConfig->autodj_queue_length,
                    'crossfade' => $backendConfig->crossfade,
                ],
            ],
            'media' => $this->mediaEntries,
            'playlists' => $this->playlistEntries,
        ];
    }
}
