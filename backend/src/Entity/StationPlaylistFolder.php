<?php

declare(strict_types=1);

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_playlist_folders')
]
final class StationPlaylistFolder implements
    Interfaces\PathAwareInterface,
    Interfaces\StationAwareInterface,
    Interfaces\StationCloneAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public Station $station;

    public function setStation(Station $station): void
    {
        $this->station = $station;
    }

    #[ORM\ManyToOne(fetch: 'EAGER', inversedBy: 'folders')]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public StationPlaylist $playlist;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $playlist_id;

    #[ORM\Column(length: 500)]
    public string $path {
        get => $this->path;
        set => $this->truncateString($value, 500);
    }

    /** @var Collection<int, StationPlaylistMedia> */
    #[
        ORM\OneToMany(targetEntity: StationPlaylistMedia::class, mappedBy: 'folder', fetch: 'EXTRA_LAZY'),
        ORM\OrderBy(['weight' => 'ASC'])
    ]
    public private(set) Collection $media_items;

    public function __construct(Station $station, StationPlaylist $playlist, string $path)
    {
        $this->station = $station;
        $this->playlist = $playlist;
        $this->path = $path;

        $this->media_items = new ArrayCollection();
    }

    public function __clone()
    {
        $this->media_items = new ArrayCollection();
    }
}
