<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Entity\Interfaces\PathAwareInterface;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_playlist_folders')
]
class StationPlaylistFolder implements PathAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'folders')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationPlaylist $playlist;

    #[ORM\Column(length: 500)]
    protected string $path;

    public function __construct(Station $station, StationPlaylist $playlist, string $path)
    {
        $this->station = $station;
        $this->playlist = $playlist;
        $this->path = $path;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getPlaylist(): StationPlaylist
    {
        return $this->playlist;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $this->truncateString($path, 500);
    }
}
