<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_playlist_folders')
]
final class StationPlaylistFolder implements
    Interfaces\PathAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public Station $station;

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'folders')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist;

    #[ORM\Column(length: 500)]
    public string $path;

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

    public function setStation(Station $station): void
    {
        $this->station = $station;
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
