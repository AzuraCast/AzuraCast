<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_history")
 * @Entity
 */
class SongHistory extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="song_id", type="string", length=50) */
    protected $song_id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /**
     * @ManyToOne(targetEntity="Song", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $song;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}