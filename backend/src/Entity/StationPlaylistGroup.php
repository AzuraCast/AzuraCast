<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

// @TODO: generate migration when ready

#[
    ORM\Entity,
    ORM\Table(name: 'station_playlist_group'),
    Attributes\Auditable
]
final class StationPlaylistGroup implements JsonSerializable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'playlists')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $playlist_id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'playlist_groups')]
    #[ORM\JoinColumn(name: 'playlist_group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist_group;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $playlist_group_id;

    #[ORM\Column]
    public int $weight = 0;

    #[ORM\Column]
    public bool $is_queued = true;

    #[ORM\Column]
    public int $last_played = 0;

    public function __construct(
        StationPlaylist $playlist,
        StationPlaylist $playlistGroup
    ) {
        $this->playlist = $playlist;
        $this->playlist_group = $playlistGroup;
    }

    public function played(?int $timestamp = null): void
    {
        $this->last_played = $timestamp ?? time();
        $this->is_queued = false;
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
        ];
    }

    public function __clone()
    {
        $this->last_played = 0;
        $this->is_queued = false;
    }
}
