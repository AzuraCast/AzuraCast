<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enums\AnalyticsIntervals;
use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity(readOnly: true),
    ORM\Table(name: 'analytics'),
    ORM\Index(name: 'search_idx', columns: ['type', 'moment']),
    ORM\UniqueConstraint(name: 'stats_unique_idx', columns: ['station_id', 'type', 'moment'])
]
final class Analytics implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public readonly ?Station $station;

    #[ORM\Column(type: 'string', length: 15, enumType: AnalyticsIntervals::class)]
    public readonly AnalyticsIntervals $type;

    #[ORM\Column(type: 'datetime_immutable', precision: 0)]
    public readonly DateTimeImmutable $moment;

    #[ORM\Column]
    public readonly int $number_min;

    #[ORM\Column]
    public readonly int $number_max;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    public readonly string $number_avg;

    #[ORM\Column(nullable: true)]
    public readonly ?int $number_unique;

    public function __construct(
        mixed $moment,
        ?Station $station = null,
        AnalyticsIntervals $type = AnalyticsIntervals::Daily,
        int $numberMin = 0,
        int $numberMax = 0,
        float $numberAvg = 0,
        ?int $numberUnique = null
    ) {
        $this->moment = Time::toUtcCarbonImmutable($moment);

        $this->station = $station;
        $this->type = $type;

        $this->number_min = $numberMin;
        $this->number_max = $numberMax;
        $this->number_avg = (string)round($numberAvg, 2);
        $this->number_unique = $numberUnique;
    }

    public function getNumberAvg(): float
    {
        return round((float)$this->number_avg, 2);
    }
}
