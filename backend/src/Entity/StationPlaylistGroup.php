<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\PlaylistGroupAllowedRequests;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;
use OpenApi\Attributes as OA;

#[
    OA\Schema(
        type: "object",
        properties: [
            new OA\Property(
                property: 'name',
                description: 'The playlist name.',
                type: 'string',
                example: 'My Playlist',
                readOnly: true
            ),
        ]
    ),
    ORM\Entity,
    ORM\Table(name: 'station_playlist_group'),
    Attributes\Auditable
]
final class StationPlaylistGroup implements JsonSerializable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'playlist_groups')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $playlist_id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'playlists')]
    #[ORM\JoinColumn(name: 'playlist_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist_group;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $playlist_group_id;

    #[
        OA\Property(example: 1),
        ORM\Column
    ]
    public int $weight = 0;

    #[ORM\Column]
    public bool $is_queued = true;

    #[ORM\Column]
    public int $last_played = 0;

    #[
        OA\Property(example: 0),
        ORM\Column
    ]
    public int $consecutive_plays = 0;

    #[ORM\Column]
    public int $consecutive_plays_count = 0;

    #[
        OA\Property(example: 'any'),
        ORM\Column(type: 'string', enumType: PlaylistGroupAllowedRequests::class)
    ]
    public PlaylistGroupAllowedRequests $allowed_requests = PlaylistGroupAllowedRequests::Any;

    public function __construct(
        StationPlaylist $playlist,
        StationPlaylist $playlistGroup
    ) {
        $this->playlist = $playlist;
        $this->playlist_group = $playlistGroup;
    }

    public function played(?int $timestamp = null, bool $forceAdvance = false): bool
    {
        $this->last_played = $timestamp ?? time();

        if ($this->consecutive_plays > 0) {
            $this->consecutive_plays_count++;
        }

        if (
            !$forceAdvance
            && $this->consecutive_plays > 0
            && $this->consecutive_plays_count < $this->consecutive_plays
        ) {
            return false;
        }

        $this->is_queued = false;
        $this->consecutive_plays_count = 0;
        return true;
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize(): array
    {
        return [
            'id' => $this->playlist->id,
            'name' => $this->playlist->name,
            'weight' => $this->weight,
            'consecutive_plays' => $this->consecutive_plays,
            'allowed_requests' => $this->allowed_requests->value,
        ];
    }

    public function __clone()
    {
        $this->last_played = 0;
        $this->is_queued = false;
        $this->consecutive_plays_count = 0;
    }
}
