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
class StationRequest implements
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\ManyToOne(inversedBy: 'requests')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $station_id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'track_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationMedia $track;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $track_id;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    protected DateTimeImmutable $timestamp;

    #[ORM\Column]
    protected bool $skip_delay = false;

    #[ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true)]
    protected ?DateTimeImmutable $played_at = null;

    #[ORM\Column(length: 40)]
    protected string $ip;

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
        $this->ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getTrack(): StationMedia
    {
        return $this->track;
    }

    public function getTimestamp(): DateTimeImmutable
    {
        return $this->timestamp;
    }

    public function skipDelay(): bool
    {
        return $this->skip_delay;
    }

    public function getPlayedAt(): ?DateTimeImmutable
    {
        return $this->played_at;
    }

    public function setPlayedAt(mixed $playedAt): void
    {
        $this->played_at = Time::toNullableUtcCarbonImmutable($playedAt);
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function shouldPlayNow(?DateTimeImmutable $now = null): bool
    {
        if ($this->skip_delay) {
            return true;
        }

        $station = $this->station;

        $thresholdMins = (int)$station->getRequestDelay();
        $thresholdMins += random_int(0, $thresholdMins);

        $now = (null !== $now)
            ? CarbonImmutable::instance($now)
            : Time::nowUtc();

        return $now->subMinutes($thresholdMins)->gt($this->timestamp);
    }
}
