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
class StationPlaylistMedia implements JsonSerializable, IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'media_items')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationPlaylist $playlist;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $playlist_id;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'playlists')]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationMedia $media;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $media_id;

    #[ORM\Column]
    protected int $weight = 0;

    #[ORM\Column]
    protected bool $is_queued = true;

    #[ORM\Column]
    protected int $last_played = 0;

    public function __construct(StationPlaylist $playlist, StationMedia $media)
    {
        $this->playlist = $playlist;
        $this->media = $media;
    }

    public function getPlaylist(): StationPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(StationPlaylist $playlist): void
    {
        $this->playlist = $playlist;
    }

    public function getMedia(): StationMedia
    {
        return $this->media;
    }

    public function getWeight(): int
    {
        return $this->weight;
    }

    public function setWeight(int $weight): void
    {
        $this->weight = $weight;
    }

    public function getLastPlayed(): int
    {
        return $this->last_played;
    }

    public function played(int $timestamp = null): void
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
            'id'     => $this->playlist->getId(),
            'name'   => $this->playlist->getName(),
            'weight' => $this->weight,
        ];
    }

    public function __clone()
    {
        $this->last_played = 0;
        $this->is_queued = false;
    }
}
