<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

#[
    ORM\Entity,
    ORM\Table(name: 'station_playlist_media')
]
final class StationPlaylistMedia implements JsonSerializable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'playlists')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly StationMedia $media;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $media_id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'media_items')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $playlist_id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'media_items')]
    #[ORM\JoinColumn(name: 'folder_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public ?StationPlaylistFolder $folder = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $folder_id = null;

    #[ORM\Column]
    public int $weight = 0;

    #[ORM\Column]
    public bool $is_queued = true;

    #[ORM\Column]
    public int $last_played = 0;

    public function __construct(
        StationPlaylist $playlist,
        StationMedia $media,
        ?StationPlaylistFolder $folder = null
    ) {
        $this->playlist = $playlist;
        $this->media = $media;
        $this->folder = $folder;
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
            'folder' => $this->folder?->path,
        ];
    }

    public function __clone()
    {
        $this->last_played = 0;
        $this->is_queued = false;
    }
}
