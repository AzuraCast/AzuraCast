<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\SongInterface;
use App\Utilities\Time;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[
    ORM\Entity,
    ORM\Table(name: 'song_history'),
    ORM\Index(name: 'idx_is_visible', columns: ['is_visible']),
    ORM\Index(name: 'idx_timestamp_start', columns: ['timestamp_start']),
    ORM\Index(name: 'idx_timestamp_end', columns: ['timestamp_end'])
]
final class SongHistory implements
    Interfaces\SongInterface,
    Interfaces\IdentifiableEntityInterface,
    Interfaces\StationAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    /** @var int The expected delay between when a song history record is registered and when listeners hear it. */
    public const int PLAYBACK_DELAY_SECONDS = 5;

    /** @var int */
    public const int DEFAULT_DAYS_TO_KEEP = 60;

    #[ORM\ManyToOne(inversedBy: 'history')]
    #[ORM\JoinColumn(name: 'station_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public readonly Station $station;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    public private(set) int $station_id;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'playlist_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public ?StationPlaylist $playlist = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $playlist_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'streamer_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public ?StationStreamer $streamer = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $streamer_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'media_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public ?StationMedia $media = null {
        set {
            $this->media = $value;

            if (null !== $value) {
                $this->duration = $value->getCalculatedLength();
            }
        }
    }

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $media_id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'request_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public ?StationRequest $request = null;

    /* TODO Remove direct identifier access. */
    #[ORM\Column(nullable: true, insertable: false, updatable: false)]
    public private(set) ?int $request_id = null;

    #[ORM\Column(type: 'datetime_immutable', precision: 6)]
    public readonly DateTimeImmutable $timestamp_start;

    #[ORM\Column(type: 'float', nullable: true)]
    public ?float $duration = null;

    #[ORM\Column(nullable: true)]
    public ?int $listeners_start = null;

    #[ORM\Column(type: 'datetime_immutable', precision: 6, nullable: true)]
    public ?DateTimeImmutable $timestamp_end = null;

    #[ORM\Column(nullable: true)]
    public ?int $listeners_end = 0;

    #[ORM\Column(nullable: true)]
    public ?int $unique_listeners = 0;

    #[ORM\Column]
    public int $delta_total = 0 {
        set => $this->truncateSmallInt($value);
    }

    #[ORM\Column]
    public int $delta_positive = 0 {
        set => $this->truncateSmallInt($value);
    }

    #[ORM\Column]
    public int $delta_negative = 0 {
        set => $this->truncateSmallInt($value);
    }

    #[ORM\Column(type: 'json', nullable: true)]
    public ?array $delta_points = null;

    #[ORM\Column]
    public bool $is_visible = true;

    public function __construct(
        Station $station,
        SongInterface $song
    ) {
        $this->setSong($song);
        $this->station = $station;
        $this->timestamp_start = Time::nowUtc();
    }

    public function addDeltaPoint(int $deltaPoint): void
    {
        $deltaPoints = $this->delta_points ?? [];

        if (0 === count($deltaPoints)) {
            $this->listeners_start = $deltaPoint;
        }

        $deltaPoints[] = $deltaPoint;
        $this->delta_points = $deltaPoints;
    }

    public function setListenersFromLastSong(?SongHistory $lastSong): void
    {
        if (null === $lastSong) {
            $this->addDeltaPoint(0);
            return;
        }

        $deltaPoints = $lastSong->delta_points ?? [];

        $lastDeltaPoint = array_pop($deltaPoints);
        if (null !== $lastDeltaPoint) {
            $this->addDeltaPoint($lastDeltaPoint);
        }
    }

    public function updateVisibility(): void
    {
        $this->is_visible = !($this->playlist instanceof StationPlaylist) || !$this->playlist->is_jingle;
    }

    public function playbackEnded(): void
    {
        $nowUtc = Time::nowUtc();
        $this->timestamp_end = $nowUtc;

        if (!$this->duration) {
            $this->duration = $nowUtc->diffInSeconds($this->timestamp_start, true);
        }

        $deltaPoints = (array)$this->delta_points;

        if (0 !== count($deltaPoints)) {
            $this->listeners_end = end($deltaPoints);
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

            $this->delta_positive = (int)$deltaPositive;
            $this->delta_negative = (int)$deltaNegative;
            $this->delta_total = (int)$deltaTotal;
        } else {
            $this->listeners_end = 0;
            $this->delta_positive = 0;
            $this->delta_negative = 0;
            $this->delta_total = 0;
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
        $sh = new self($queue->station, $queue);
        $sh->media = $queue->media;
        $sh->request = $queue->request;
        $sh->playlist = $queue->playlist;
        $sh->duration = $queue->duration;
        $sh->updateVisibility();

        return $sh;
    }
}
