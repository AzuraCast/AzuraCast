<?php
namespace Entity;

/**
 * @Table(name="analytics", indexes={
 *   @index(name="search_idx", columns={"type", "timestamp"})
 * })
 * @Entity
 */
class Analytics
{
    /** @var string Log all analytics data across the system. */
    public const LEVEL_ALL = 'all';

    /** @var string Suppress any IP-based logging and use aggregate logging only. */
    public const LEVEL_NO_IP = 'no_ip';

    /** @var string No analytics data collected of any sort. */
    public const LEVEL_NONE = 'none';

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int|null
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $station_id;

    /**
     * @Column(name="type", type="string", length=15)
     * @var string
     */
    protected $type;

    /**
     * @Column(name="timestamp", type="integer")
     * @var int
     */
    protected $timestamp;

    /**
     * @Column(name="number_min", type="integer")
     * @var int
     */
    protected $number_min;

    /**
     * @Column(name="number_max", type="integer")
     * @var int
     */
    protected $number_max;

    /**
     * @Column(name="number_avg", type="integer")
     * @var int
     */
    protected $number_avg;

    /**
     * @ManyToOne(targetEntity="Station")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station|null
     */
    protected $station;

    /**
     * Analytics constructor.
     * @param Station|null $station
     * @param $type
     * @param null $timestamp
     * @param int $number_min
     * @param int $number_max
     * @param int $number_avg
     */
    public function __construct(Station $station = null, $type = 'day', $timestamp = null, $number_min = 0, $number_max = 0, $number_avg = 0)
    {
        $this->station = $station;
        $this->type = $type;
        $this->timestamp = $timestamp ?? time();
        $this->number_min = $number_min;
        $this->number_max = $number_max;
        $this->number_avg = $number_avg;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
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
    public function getNumberMin(): int
    {
        return $this->number_min;
    }
    /**
     * @return int
     */
    public function getNumberMax(): int
    {
        return $this->number_max;
    }

    /**
     * @return int
     */
    public function getNumberAvg(): int
    {
        return $this->number_avg;
    }

    /**
     * @return Station|null
     */
    public function getStation()
    {
        return $this->station;
    }
}