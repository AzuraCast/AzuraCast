<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_streams")
 * @Entity
 */
class StationStream extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->is_default = 0;
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="is_default", type="boolean") */
    protected $is_default;

    /** @Column(name="name", type="string", length=75, nullable=true) */
    protected $name;

    /** @Column(name="type", type="string", length=50, nullable=true) */
    protected $type;

    /** @Column(name="stream_url", type="string", length=150, nullable=true) */
    protected $stream_url;

    /** @Column(name="nowplaying_url", type="string", length=100, nullable=true) */
    protected $nowplaying_url;

    /** @Column(name="nowplaying_data", type="json", nullable=true) */
    protected $nowplaying_data;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="streams")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;
}