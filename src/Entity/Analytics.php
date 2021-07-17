<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'analytics'),
    ORM\Index(columns: ['type', 'moment'], name: 'search_idx'),
    ORM\UniqueConstraint(name: 'stats_unique_idx', columns: ['station_id', 'type', 'moment'])
]
class Analytics implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    /** @var string Log all analytics data across the system. */
    public const LEVEL_ALL = 'all';

    /** @var string Suppress any IP-based logging and use aggregate logging only. */
    public const LEVEL_NO_IP = 'no_ip';

    /** @var string No analytics data collected of any sort. */
    public const LEVEL_NONE = 'none';

    public const INTERVAL_DAILY = 'day';
    public const INTERVAL_HOURLY = 'hour';

    #[ORM\Column(nullable: true)]
    protected ?int $station_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Station $station = null;

    #[ORM\Column(length: 15)]
    protected string $type;

    #[ORM\Column(type: 'carbon_immutable')]
    protected CarbonImmutable $moment;

    #[ORM\Column]
    protected int $number_min;

    #[ORM\Column]
    protected int $number_max;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    protected string $number_avg;

    #[ORM\Column(nullable: true)]
    protected ?int $number_unique = null;

    public function __construct(
        DateTimeInterface $moment,
        ?Station $station = null,
        string $type = self::INTERVAL_DAILY,
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
        if (null === $this->station) {
            throw new \RuntimeException('Cannot get moment in station timezone; no station associated.');
        }

        $tz = $this->station->getTimezoneObject();
        return CarbonImmutable::parse($this->moment, $tz)->shiftTimezone($tz);
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
