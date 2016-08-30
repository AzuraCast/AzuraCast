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
        $this->include_in_automation = false;
        $this->weight = 3;

        $this->media = new ArrayCollection;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="name", type="string", length=200) */
    protected $name;

    public function getShortName()
    {
        return Station::getStationShortName($this->name);
    }

    /** @Column(name="weight", type="smallint") */
    protected $weight;

    /** @Column(name="include_in_automation", type="boolean", nullable=false) */
    protected $include_in_automation;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="playlists")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToMany(targetEntity="StationMedia", mappedBy="playlists", fetch="EXTRA_LAZY")
     */
    protected $media;
}