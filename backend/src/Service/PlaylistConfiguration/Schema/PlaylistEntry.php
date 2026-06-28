<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration\Schema;

use App\Entity\Enums\PlaylistOrders;
use App\Entity\Enums\PlaylistRemoteTypes;
use App\Entity\Enums\PlaylistSources;
use App\Entity\Enums\PlaylistTypes;
use App\Utilities\Types;
use JsonSerializable;

final class PlaylistEntry implements JsonSerializable
{
    /**
     * @param string[] $backendOptions
     * @param PlaylistFolderEntry[] $folders
     * @param PlaylistMediaEntry[] $media
     * @param PlaylistScheduleEntry[] $schedules
     * @param PlaylistMemberEntry[] $members
     */
    public function __construct(
        public readonly string $ref,
        public readonly string $name,
        public readonly PlaylistTypes $type,
        public readonly PlaylistSources $source,
        public readonly PlaylistOrders $order,
        public readonly int $weight,
        public readonly bool $isEnabled,
        public readonly bool $isJingle,
        public readonly bool $avoidDuplicates,
        public readonly bool $includeInRequests,
        public readonly bool $includeInOnDemand,
        public readonly int $playPerSongs,
        public readonly int $playPerMinutes,
        public readonly int $playPerHourMinute,
        public readonly array $backendOptions,
        public readonly ?string $remoteUrl,
        public readonly ?PlaylistRemoteTypes $remoteType,
        public readonly int $remoteBuffer,
        public readonly ?string $description,
        public array $folders,
        public array $media,
        public array $schedules,
        public array $members,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $config = Types::array($data['config'] ?? []);

        $remoteType = Types::stringOrNull($config['remote_type'] ?? null);

        return new self(
            ref: Types::string($data['ref'] ?? null),
            name: Types::string($data['name'] ?? null),
            type: PlaylistTypes::from(Types::string($config['type'] ?? null)),
            source: PlaylistSources::from(Types::string($config['source'] ?? null)),
            order: PlaylistOrders::from(Types::string($config['order'] ?? null)),
            weight: Types::int($config['weight'] ?? null),
            isEnabled: Types::bool($config['is_enabled'] ?? null),
            isJingle: Types::bool($config['is_jingle'] ?? null),
            avoidDuplicates: Types::bool($config['avoid_duplicates'] ?? null),
            includeInRequests: Types::bool($config['include_in_requests'] ?? null),
            includeInOnDemand: Types::bool($config['include_in_on_demand'] ?? null),
            playPerSongs: Types::int($config['play_per_songs'] ?? null),
            playPerMinutes: Types::int($config['play_per_minutes'] ?? null),
            playPerHourMinute: Types::int($config['play_per_hour_minute'] ?? null),
            backendOptions: array_map('strval', Types::array($config['backend_options'] ?? [])),
            remoteUrl: Types::stringOrNull($config['remote_url'] ?? null),
            remoteType: ($remoteType !== null) ? PlaylistRemoteTypes::tryFrom($remoteType) : null,
            remoteBuffer: Types::int($config['remote_buffer'] ?? null),
            description: Types::stringOrNull($config['description'] ?? null),
            folders: array_map(
                static fn(mixed $item): PlaylistFolderEntry => PlaylistFolderEntry::fromArray(Types::array($item)),
                array_values(Types::array($data['folders'] ?? []))
            ),
            media: array_map(
                static fn(mixed $item): PlaylistMediaEntry => PlaylistMediaEntry::fromArray(Types::array($item)),
                array_values(Types::array($data['media'] ?? []))
            ),
            schedules: array_map(
                static fn(mixed $item): PlaylistScheduleEntry => PlaylistScheduleEntry::fromArray(Types::array($item)),
                array_values(Types::array($data['schedules'] ?? []))
            ),
            members: array_map(
                static fn(mixed $item): PlaylistMemberEntry => PlaylistMemberEntry::fromArray(Types::array($item)),
                array_values(Types::array($data['members'] ?? []))
            ),
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'ref' => $this->ref,
            'name' => $this->name,
            'config' => [
                'type' => $this->type->value,
                'source' => $this->source->value,
                'order' => $this->order->value,
                'weight' => $this->weight,
                'is_enabled' => $this->isEnabled,
                'is_jingle' => $this->isJingle,
                'avoid_duplicates' => $this->avoidDuplicates,
                'include_in_requests' => $this->includeInRequests,
                'include_in_on_demand' => $this->includeInOnDemand,
                'play_per_songs' => $this->playPerSongs,
                'play_per_minutes' => $this->playPerMinutes,
                'play_per_hour_minute' => $this->playPerHourMinute,
                'backend_options' => array_values(array_filter($this->backendOptions)),
                'remote_url' => $this->remoteUrl,
                'remote_type' => $this->remoteType?->value,
                'remote_buffer' => $this->remoteBuffer,
                'description' => $this->description,
            ],
            'folders' => $this->folders,
            'media' => $this->media,
            'schedules' => $this->schedules,
            'members' => $this->members,
        ];
    }
}
