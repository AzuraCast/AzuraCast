<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * @ORM\Table(name="station_playlist_media")
 * @ORM\Entity()
 */
class StationPlaylistMedia implements JsonSerializable
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="playlist_id", type="integer")
     * @var int
     */
    protected $playlist_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationPlaylist", inversedBy="media_items", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationPlaylist
     */
    protected $playlist;

    /**
     * @ORM\Column(name="media_id", type="integer")
     * @var int
     */
    protected $media_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationMedia", inversedBy="playlists", fetch="EAGER")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationMedia
     */
    protected $media;

    /**
     * @ORM\Column(name="weight", type="integer")
     * @var int
     */
    protected $weight;

    /**
     * @ORM\Column(name="last_played", type="integer")
     * @var int
     */
    protected $last_played;

    public function __construct(StationPlaylist $playlist, StationMedia $media)
    {
        $this->playlist = $playlist;
        $this->media = $media;
        $this->weight = 0;
        $this->last_played = 0;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPlaylist(): StationPlaylist
    {
        return $this->playlist;
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
    }

    /**
     * @inheritDoc
     */
    public function jsonSerialize()
    {
        return [
            'id' => $this->playlist->getId(),
            'name' => $this->playlist->getName(),
            'weight' => (int)$this->weight,
        ];
    }
}
