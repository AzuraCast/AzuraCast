<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="analytics", indexes={
 *   @ORM\Index(name="search_idx", columns={"type", "timestamp"})
 * })
 * @ORM\Entity
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
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $station_id;

    /**
     * @ORM\Column(name="type", type="string", length=15)
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(name="timestamp", type="integer")
     * @var int
     */
    protected $timestamp;

    /**
     * @ORM\Column(name="number_min", type="integer")
     * @var int
     */
    protected $number_min;

    /**
     * @ORM\Column(name="number_max", type="integer")
     * @var int
     */
    protected $number_max;

    /**
     * @ORM\Column(name="number_avg", type="integer")
     * @var int
     */
    protected $number_avg;

    /**
     * @ORM\ManyToOne(targetEntity="Station")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station|null
     */
    protected $station;

    public function __construct(
        Station $station = null,
        $type = 'day',
        $timestamp = null,
        $number_min = 0,
        $number_max = 0,
        $number_avg = 0
    ) {
        $this->station = $station;
        $this->type = $type;
        $this->timestamp = $timestamp ?? time();
        $this->number_min = $number_min;
        $this->number_max = $number_max;
        $this->number_avg = $number_avg;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function getNumberMin(): int
    {
        return $this->number_min;
    }

    public function getNumberMax(): int
    {
        return $this->number_max;
    }

    public function getNumberAvg(): int
    {
        return $this->number_avg;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }
}
