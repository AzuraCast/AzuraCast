<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use Carbon\CarbonImmutable;
use DateTimeInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;
use RuntimeException;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'analytics'),
    ORM\Index(columns: ['type', 'moment'], name: 'search_idx'),
    ORM\UniqueConstraint(name: 'stats_unique_idx', columns: ['station_id', 'type', 'moment'])
]
class Analytics implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    protected ?Station $station = null;

    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    protected ?int $station_id = null;

    #[ORM\Column(type: 'string', length: 15, enumType: AnalyticsIntervals::class)]
    protected AnalyticsIntervals $type;

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
        AnalyticsIntervals $type = AnalyticsIntervals::Daily,
        int $numberMin = 0,
        int $numberMax = 0,
        float $numberAvg = 0,
        ?int $numberUnique = null
    ) {
        $utc = new DateTimeZone('UTC');

        $this->moment = CarbonImmutable::parse($moment, $utc)->shiftTimezone($utc);

        $this->station = $station;
        $this->type = $type;

        $this->number_min = $numberMin;
        $this->number_max = $numberMax;
        $this->number_avg = (string)round($numberAvg, 2);
        $this->number_unique = $numberUnique;
    }

    public function getStation(): ?Station
    {
        return $this->station;
    }

    public function getType(): AnalyticsIntervals
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
            throw new RuntimeException('Cannot get moment in station timezone; no station associated.');
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
