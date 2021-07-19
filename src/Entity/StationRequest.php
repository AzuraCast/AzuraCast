<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'station_requests')
]
class StationRequest implements IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;

    #[ORM\Column(nullable: false)]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(nullable: false)]
    protected int $track_id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'track_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected StationMedia $track;

    #[ORM\Column]
    protected int $timestamp;

    #[ORM\Column]
    protected bool $skip_delay = false;

    #[ORM\Column]
    protected int $played_at = 0;

    #[ORM\Column(length: 40)]
    protected string $ip;

    public function __construct(
        Station $station,
        StationMedia $track,
        string $ip = null,
        bool $skipDelay = false
    ) {
        $this->station = $station;
        $this->track = $track;

        $this->timestamp = time();
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

    public function getTimestamp(): int
    {
        return $this->timestamp;
    }

    public function skipDelay(): bool
    {
        return $this->skip_delay;
    }

    public function getPlayedAt(): int
    {
        return $this->played_at;
    }

    public function setPlayedAt(int $played_at): void
    {
        $this->played_at = $played_at;
    }

    public function getIp(): string
    {
        return $this->ip;
    }

    public function shouldPlayNow(CarbonInterface $now = null): bool
    {
        if ($this->skip_delay) {
            return true;
        }

        $station = $this->station;
        $stationTz = new DateTimeZone($station->getTimezone());

        if (null === $now) {
            $now = CarbonImmutable::now($stationTz);
        }

        $thresholdMins = (int)$station->getRequestDelay();
        $thresholdMins += random_int(0, $thresholdMins);

        $cued = CarbonImmutable::createFromTimestamp($this->timestamp);
        return $now->subMinutes($thresholdMins)->gt($cued);
    }
}
