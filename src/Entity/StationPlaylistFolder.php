<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Entity\Traits\TruncateStrings;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="station_playlist_folders")
 * @ORM\Entity()
 */
class StationPlaylistFolder implements PathAwareInterface
{
    use TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\ManyToOne(targetEntity="Station")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\ManyToOne(targetEntity="StationPlaylist", inversedBy="media_items", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationPlaylist
     */
    protected $playlist;

    /**
     * @ORM\Column(name="path", type="string", length=500)
     * @var string
     */
    protected $path;

    public function __construct(Station $station, StationPlaylist $playlist, string $path)
    {
        $this->station = $station;
        $this->playlist = $playlist;
        $this->path = $path;
    }

    public function getId(): ?int
    {
        return $this->id;
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
