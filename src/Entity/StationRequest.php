<?php

namespace App\Entity;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeZone;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="station_requests")
 * @ORM\Entity()
 */
class StationRequest
{
    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     * @var int|null
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="media")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="track_id", type="integer")
     * @var int
     */
    protected $track_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationMedia")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="track_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationMedia
     */
    protected $track;

    /**
     * @ORM\Column(name="timestamp", type="integer")
     * @var int
     */
    protected $timestamp;

    /**
     * @ORM\Column(name="skip_delay", type="boolean", nullable=false)
     * @var bool If set to "true", this request was set by an administrator and skips various delays.
     */
    protected $skip_delay = false;

    /**
     * @ORM\Column(name="played_at", type="integer")
     * @var int
     */
    protected $played_at;

    /**
     * @ORM\Column(name="ip", type="string", length=40)
     * @var string
     */
    protected $ip;

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
        $this->played_at = 0;

        $this->ip = $ip ?? $_SERVER['REMOTE_ADDR'];
    }

    public function getId(): ?int
    {
        return $this->id;
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
        $threshold = $now->subMinutes($thresholdMins);

        return $threshold->gt($cued);
    }
}
