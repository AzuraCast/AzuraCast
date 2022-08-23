<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\SongInterface;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'song_history'),
    ORM\Index(columns: ['is_visible'], name: 'idx_is_visible'),
    ORM\Index(columns: ['timestamp_start'], name: 'idx_timestamp_start'),
    ORM\Index(columns: ['timestamp_end'], name: 'idx_timestamp_end')
]
class SongHistory implements
    Interfaces\SongInterface,
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    /** @var int The expected delay between when a song history record is registered and when listeners hear it. */
    public const PLAYBACK_DELAY_SECONDS = 5;

    /** @var int */
    public const DEFAULT_DAYS_TO_KEEP = 60;

    #[ORM\Column(nullable: false)]
    protected int $station_id;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected Station $station;

    #[ORM\Column(nullable: true)]
    protected ?int $playlist_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?StationPlaylist $playlist = null;

    #[ORM\Column(nullable: true)]
    protected ?int $streamer_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?StationStreamer $streamer = null;

    #[ORM\Column(nullable: true)]
    protected ?int $media_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?StationMedia $media = null;

    #[ORM\Column(nullable: true)]
    protected ?int $request_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected ?StationRequest $request = null;

    #[ORM\Column]
    protected int $timestamp_start = 0;

    #[ORM\Column(nullable: true)]
    protected ?int $duration = null;

    #[ORM\Column(nullable: true)]
    protected ?int $listeners_start = null;

    #[ORM\Column]
    protected int $timestamp_end = 0;

    #[ORM\Column(nullable: true)]
    protected ?int $listeners_end = 0;

    #[ORM\Column(nullable: true)]
    protected ?int $unique_listeners = 0;

    #[ORM\Column]
    protected int $delta_total = 0;

    #[ORM\Column]
    protected int $delta_positive = 0;

    #[ORM\Column]
    protected int $delta_negative = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    protected mixed $delta_points = null;

    #[ORM\Column]
    protected bool $is_visible = true;

    public function __construct(
        Station $station,
        SongInterface $song
    ) {
        $this->setSong($song);
        $this->station = $station;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    public function setPlaylist(StationPlaylist $playlist = null): void
    {
        $this->playlist = $playlist;
    }

    public function getStreamer(): ?StationStreamer
    {
        return $this->streamer;
    }

    public function setStreamer(?StationStreamer $streamer): void
    {
        $this->streamer = $streamer;
    }

    public function getMedia(): ?StationMedia
    {
        return $this->media;
    }

    public function setMedia(?StationMedia $media = null): void
    {
        $this->media = $media;

        if (null !== $media) {
            $this->setDuration($media->getCalculatedLength());
        }
    }

    public function getRequest(): ?StationRequest
    {
        return $this->request;
    }

    public function setRequest(?StationRequest $request): void
    {
        $this->request = $request;
    }

    public function getTimestampStart(): int
    {
        return $this->timestamp_start;
    }

    public function setTimestampStart(int $timestamp_start): void
    {
        $this->timestamp_start = $timestamp_start;
    }

    public function getDuration(): ?int
    {
        return $this->duration;
    }

    public function setDuration(?int $duration): void
    {
        $this->duration = $duration;
    }

    public function getListenersStart(): ?int
    {
        return $this->listeners_start;
    }

    public function setListenersStart(?int $listeners_start): void
    {
        $this->listeners_start = $listeners_start;
    }

    public function getTimestampEnd(): int
    {
        return $this->timestamp_end;
    }

    public function setTimestampEnd(int $timestamp_end): void
    {
        $this->timestamp_end = $timestamp_end;

        if (!$this->duration) {
            $this->duration = $timestamp_end - $this->timestamp_start;
        }
    }

    public function getTimestamp(): int
    {
        return $this->timestamp_start;
    }

    public function getListenersEnd(): ?int
    {
        return $this->listeners_end;
    }

    public function setListenersEnd(?int $listeners_end): void
    {
        $this->listeners_end = $listeners_end;
    }

    public function getUniqueListeners(): ?int
    {
        return $this->unique_listeners;
    }

    public function setUniqueListeners(?int $unique_listeners): void
    {
        $this->unique_listeners = $unique_listeners;
    }

    public function getListeners(): int
    {
        return (int)$this->listeners_start;
    }

    public function getDeltaTotal(): int
    {
        return $this->delta_total;
    }

    public function setDeltaTotal(int $delta_total): void
    {
        $this->delta_total = $this->truncateSmallInt($delta_total);
    }

    public function getDeltaPositive(): int
    {
        return $this->delta_positive;
    }

    public function setDeltaPositive(int $delta_positive): void
    {
        $this->delta_positive = $this->truncateSmallInt($delta_positive);
    }

    public function getDeltaNegative(): int
    {
        return $this->delta_negative;
    }

    public function setDeltaNegative(int $delta_negative): void
    {
        $this->delta_negative = $this->truncateSmallInt($delta_negative);
    }

    public function getDeltaPoints(): mixed
    {
        return $this->delta_points;
    }

    public function addDeltaPoint(int $delta_point): void
    {
        $delta_points = (array)$this->delta_points;

        if (0 === count($delta_points)) {
            $this->setListenersStart($delta_point);
        }

        $delta_points[] = $delta_point;
        $this->delta_points = $delta_points;
    }

    public function setListenersFromLastSong(?SongHistory $lastSong): void
    {
        if (null === $lastSong) {
            $this->addDeltaPoint(0);
            return;
        }

        $deltaPoints = (array)$lastSong->getDeltaPoints();
        $lastDeltaPoint = array_pop($deltaPoints);

        $this->addDeltaPoint($lastDeltaPoint);
    }

    public function getIsVisible(): bool
    {
        return $this->is_visible;
    }

    public function setIsVisible(bool $is_visible): void
    {
        $this->is_visible = $is_visible;
    }

    public function updateVisibility(): void
    {
        $this->is_visible = !($this->playlist instanceof StationPlaylist) || !$this->playlist->getIsJingle();
    }

    /**
     * @return bool Whether the record should be shown in APIs (i.e. is not a jingle)
     */
    public function showInApis(): bool
    {
        if ($this->playlist instanceof StationPlaylist) {
            return !$this->playlist->getIsJingle();
        }
        return true;
    }

    public function playbackEnded(): void
    {
        $this->setTimestampEnd(time());

        $deltaPoints = (array)$this->getDeltaPoints();

        if (0 !== count($deltaPoints)) {
            $this->setListenersEnd(end($deltaPoints));
            reset($deltaPoints);

            $deltaPositive = 0;
            $deltaNegative = 0;
            $deltaTotal = 0;

            $previousDelta = null;
            foreach ($deltaPoints as $currentDelta) {
                if (null !== $previousDelta) {
                    $deltaDelta = $currentDelta - $previousDelta;
                    $deltaTotal += $deltaDelta;

                    if ($deltaDelta > 0) {
                        $deltaPositive += $deltaDelta;
                    } elseif ($deltaDelta < 0) {
                        $deltaNegative += (int)abs($deltaDelta);
                    }
                }

                $previousDelta = $currentDelta;
            }

            $this->setDeltaPositive((int)$deltaPositive);
            $this->setDeltaNegative((int)$deltaNegative);
            $this->setDeltaTotal((int)$deltaTotal);
        } else {
            $this->setListenersEnd(0);
            $this->setDeltaPositive(0);
            $this->setDeltaNegative(0);
            $this->setDeltaTotal(0);
        }
    }

    public function __toString(): string
    {
        if ($this->media instanceof StationMedia) {
            return (string)$this->media;
        }

        return (string)(new Song($this));
    }

    public static function fromQueue(StationQueue $queue): self
    {
        $sh = new self($queue->getStation(), $queue);
        $sh->setMedia($queue->getMedia());
        $sh->setRequest($queue->getRequest());
        $sh->setPlaylist($queue->getPlaylist());
        $sh->setDuration($queue->getDuration());
        $sh->updateVisibility();

        return $sh;
    }
}
