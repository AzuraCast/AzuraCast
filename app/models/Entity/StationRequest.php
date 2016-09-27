<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_requests")
 * @Entity(repositoryClass="Repository\StationRequestRepository")
 */
class StationRequest extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();
        $this->played_at = 0;

        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="track_id", type="integer") */
    protected $track_id;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="played_at", type="integer") */
    protected $played_at;

    /** @Column(name="ip", type="string", length=40) */
    protected $ip;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="media")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToOne(targetEntity="StationMedia")
     * @JoinColumns({
     *   @JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $track;

    /**
     * @deprecated
     * @param Station $station
     * @param $track_id
     * @param bool $is_authenticated
     * @return mixed
     * @throws \App\Exception
     */
    public static function submit(Station $station, $track_id, $is_authenticated = false)
    {
        return self::getRepository()->submit($station, $track_id, $is_authenticated);
    }
}