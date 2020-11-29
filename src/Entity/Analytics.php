<?php

namespace App\Entity;

use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="analytics", indexes={
 *   @ORM\Index(name="search_idx", columns={"type", "moment"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="stats_unique_idx", columns={"station_id", "type", "moment"})
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

    public const INTERVAL_DAILY = 'day';
    public const INTERVAL_HOURLY = 'hour';

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
     * @ORM\ManyToOne(targetEntity="Station")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station|null
     */
    protected $station;

    /**
     * @ORM\Column(name="type", type="string", length=15)
     * @var string
     */
    protected $type;

    /**
     * @ORM\Column(name="moment", type="carbon_immutable", precision=0)
     * @var CarbonImmutable
     */
    protected $moment;

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
     * @ORM\Column(name="number_avg", type="decimal", precision=10, scale=2)
     * @var string
     */
    protected $number_avg;

    /**
     * @ORM\Column(name="number_unique", type="integer", nullable=true)
     * @var int|null
     */
    protected $number_unique;

    public function __construct(
        DateTimeInterface $moment,
        ?Station $station = null,
        $type = self::INTERVAL_DAILY,
        int $number_min = 0,
        int $number_max = 0,
        float $number_avg = 0,
        ?int $number_unique = null
    ) {
        $utc = new DateTimeZone('UTC');

        $moment = CarbonImmutable::parse($moment, $utc);
        $this->moment = $moment->shiftTimezone($utc);

        $this->station = $station;
        $this->type = $type;

        $this->number_min = $number_min;
        $this->number_max = $number_max;
        $this->number_avg = (string)round($number_avg, 2);
        $this->number_unique = $number_unique;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getMoment(): CarbonImmutable
    {
        return $this->moment;
    }

    public function getMomentInStationTimeZone(): CarbonImmutable
    {
        $tz = $this->station->getTimezoneObject();
        $timestamp = CarbonImmutable::parse($this->moment, $tz);
        return $timestamp->shiftTimezone($tz);
    }

    public function getNumberMin(): int
    {
        return $this->number_min;
    }

    public function getNumberMax(): int
    {
        return $this->number_max;
    }

    public function getNumberAvg(): float
    {
        return round((float)$this->number_avg, 2);
    }

    public function getNumberUnique(): ?int
    {
        return $this->number_unique;
    }
}
