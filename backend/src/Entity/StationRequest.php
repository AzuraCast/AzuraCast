<?php

declare(strict_types=1);

namespace App\Entity;

use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_requests')
]
final class StationRequest implements
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;

    #[ORM\ManyToOne(inversedBy: 'requests')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly Station $station;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'track_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly StationMedia $track;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $track_id;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    public readonly DateTimeImmutable $timestamp;

    #[ORM\Column]
    public readonly bool $skip_delay;

    #[ORM\Column(length: 40)]
    public readonly string $ip;

    #[ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true)]
    public ?DateTimeImmutable $played_at = null {
        set(DateTimeImmutable|string|null $value) => Time::toNullableUtcCarbonImmutable($value);
    }

    public function __construct(
        Station $station,
        StationMedia $track,
        ?string $ip = null,
        bool $skipDelay = false
    ) {
        $this->station = $station;
        $this->track = $track;

        $this->timestamp = Time::nowUtc();
        $this->skip_delay = $skipDelay;
        $this->ip = $this->truncateString($ip ?? $_SERVER['REMOTE_ADDR'], 40);
    }

    public function shouldPlayNow(?DateTimeImmutable $now = null): bool
    {
        if ($this->skip_delay) {
            return true;
        }

        $station = $this->station;

        $thresholdMins = (int)$station->request_delay;
        $thresholdMins += random_int(0, $thresholdMins);

        $now = (null !== $now)
            ? CarbonImmutable::instance($now)
            : Time::nowUtc();

        return $now->subMinutes($thresholdMins)->gt($this->timestamp);
    }
}
