<?php

namespace App\Entity;

use App\ApiUtilities;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="song_history", indexes={
 *     @ORM\Index(name="idx_timestamp_start", columns={"timestamp_start"}),
 *     @ORM\Index(name="idx_timestamp_end", columns={"timestamp_end"})
 * })
 * @ORM\Entity()
 */
class SongHistory implements SongInterface
{
    use Traits\TruncateInts;
    use Traits\HasSongFields;

    /** @var int The expected delay between when a song history record is registered and when listeners hear it. */
    public const PLAYBACK_DELAY_SECONDS = 5;

    /** @var int */
    public const DEFAULT_DAYS_TO_KEEP = 60;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="history")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="playlist_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $playlist_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationPlaylist")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationPlaylist|null
     */
    protected $playlist;

    /**
     * @ORM\Column(name="streamer_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $streamer_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationStreamer")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="streamer_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationStreamer|null
     */
    protected $streamer;

    /**
     * @ORM\Column(name="media_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $media_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationMedia")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationMedia|null
     */
    protected $media;

    /**
     * @ORM\Column(name="request_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_id;

    /**
     * @ORM\ManyToOne(targetEntity="StationRequest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationRequest|null
     */
    protected $request;

    /**
     * @ORM\Column(name="timestamp_start", type="integer")
     * @var int
     */
    protected $timestamp_start;

    /**
     * @ORM\Column(name="duration", type="integer", nullable=true)
     * @var int|null
     */
    protected $duration;

    /**
     * @ORM\Column(name="listeners_start", type="integer", nullable=true)
     * @var int|null
     */
    protected $listeners_start;

    /**
     * @ORM\Column(name="timestamp_end", type="integer")
     * @var int
     */
    protected $timestamp_end;

    /**
     * @ORM\Column(name="listeners_end", type="smallint", nullable=true)
     * @var int|null
     */
    protected $listeners_end;

    /**
     * @ORM\Column(name="unique_listeners", type="smallint", nullable=true)
     * @var int|null
     */
    protected $unique_listeners;

    /**
     * @ORM\Column(name="delta_total", type="smallint")
     * @var int
     */
    protected $delta_total;

    /**
     * @ORM\Column(name="delta_positive", type="smallint")
     * @var int
     */
    protected $delta_positive;

    /**
     * @ORM\Column(name="delta_negative", type="smallint")
     * @var int
     */
    protected $delta_negative;

    /**
     * @ORM\Column(name="delta_points", type="json", nullable=true)
     * @var mixed|null
     */
    protected $delta_points;

    public function __construct(
        Station $station,
        SongInterface $song
    ) {
        $this->setSong($song);

        $this->station = $station;

        $this->timestamp_start = 0;
        $this->listeners_start = 0;

        $this->timestamp_end = 0;
        $this->listeners_end = 0;

        $this->unique_listeners = 0;

        $this->delta_total = 0;
        $this->delta_negative = 0;
        $this->delta_positive = 0;
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setMedia(StationMedia $media = null): void
    {
        $this->media = $media;

        if ($media instanceof StationMedia) {
            $this->setDuration($media->getCalculatedLength());
        }
    }

    public function getRequest(): ?StationRequest
    {
        return $this->request;
    }

    public function setRequest($request): void
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

    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    public function getListenersStart(): ?int
    {
        return $this->listeners_start;
    }

    public function setListenersStart($listeners_start): void
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
        return (int)$this->timestamp_start;
    }

    public function getListenersEnd(): ?int
    {
        return $this->listeners_end;
    }

    public function setListenersEnd($listeners_end): void
    {
        $this->listeners_end = $listeners_end;
    }

    public function getUniqueListeners(): ?int
    {
        return $this->unique_listeners;
    }

    public function setUniqueListeners($unique_listeners): void
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

    /**
     * @return mixed
     */
    public function getDeltaPoints()
    {
        return $this->delta_points;
    }

    public function addDeltaPoint($delta_point): void
    {
        $delta_points = (array)$this->delta_points;
        $delta_points[] = $delta_point;
        $this->delta_points = $delta_points;
    }

    /**
     * @return bool Whether the record should be shown in APIs (i.e. is not a jingle)
     */
    public function showInApis(): bool
    {
        if ($this->playlist instanceof StationPlaylist) {
            return !$this->playlist->isJingle();
        }
        return true;
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

        return $sh;
    }
}
