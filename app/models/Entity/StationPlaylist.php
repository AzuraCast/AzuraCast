<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_playlists")
 * @Entity
 * @HasLifecycleCallbacks
 */
class StationPlaylist extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->weight = 0;

        $this->media = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="song_id", type="string", length=50, nullable=true) */
    protected $song_id;

    /** @Column(name="name", type="string", length=200) */
    protected $name;

    public function getShortName()
    {
        return Station::getStationShortName($this->name);
    }

    /** @Column(name="weight", type="smallint") */
    protected $weight;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToMany(targetEntity="StationMedia", inversedBy="playlists")
     * @JoinTable(name="station_playlist_has_media",
     *   joinColumns={@JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")},
     *   inverseJoinColumns={@JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $media;
}