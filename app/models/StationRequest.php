<?php
namespace Entity;

/**
 * @Table(name="station_requests")
 * @Entity(repositoryClass="Entity\Repository\StationRequestRepository")
 */
class StationRequest
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="media")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="track_id", type="integer")
     * @var int
     */
    protected $track_id;

    /**
     * @ManyToOne(targetEntity="StationMedia")
     * @JoinColumns({
     *   @JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationMedia
     */
    protected $track;

    /**
     * @Column(name="timestamp", type="integer")
     * @var int
     */
    protected $timestamp;

    /**
     * @Column(name="played_at", type="integer")
     * @var int
     */
    protected $played_at;

    /**
     * @Column(name="ip", type="string", length=40)
     * @var string
     */
    protected $ip;

    public function __construct(Station $station, StationMedia $track)
    {
        $this->station = $station;
        $this->track = $track;

        $this->timestamp = time();
        $this->played_at = 0;

        $this->ip = $_SERVER['REMOTE_ADDR'];
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return StationMedia
     */
    public function getTrack(): StationMedia
    {
        return $this->track;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    /**
     * @return int
     */
    public function getPlayedAt(): int
    {
        return $this->played_at;
    }

    /**
     * @param int $played_at
     */
    public function setPlayedAt(int $played_at)
    {
        $this->played_at = $played_at;
    }

    /**
     * @return string
     */
    public function getIp(): string
    {
        return $this->ip;
    }
}