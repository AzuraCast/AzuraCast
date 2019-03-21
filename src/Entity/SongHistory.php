<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

use Psr\Http\Message\UriInterface;

/**
 * @ORM\Table(name="song_history", indexes={
 *   @ORM\Index(name="history_idx", columns={"timestamp_start","timestamp_end","listeners_start"}),
 * })
 * @ORM\Entity(repositoryClass="App\Entity\Repository\SongHistoryRepository")
 */
class SongHistory
{
    public const DEFAULT_DAYS_TO_KEEP = 60;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="song_id", type="string", length=50)
     * @var string
     */
    protected $song_id;

    /**
     * @ORM\ManyToOne(targetEntity="Song", inversedBy="history")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Song
     */
    protected $song;

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
     * @ORM\OneToOne(targetEntity="StationRequest")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationRequest|null
     */
    protected $request;

    /**
     * @ORM\Column(name="autodj_custom_uri", type="string", length=255, nullable=true)
     * @var string|null
     */
    protected $autodj_custom_uri;

    /**
     * @ORM\Column(name="timestamp_cued", type="integer", nullable=true)
     * @var int|null
     */
    protected $timestamp_cued;

    /**
     * @ORM\Column(name="sent_to_autodj", type="boolean")
     * @var bool
     */
    protected $sent_to_autodj;

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
     * @ORM\Column(name="delta_points", type="json_array", nullable=true)
     * @var mixed|null
     */
    protected $delta_points;

    public function __construct(Song $song, Station $station)
    {
        $this->song = $song;
        $this->station = $station;

        $this->sent_to_autodj = false;
        $this->timestamp_cued = 0;

        $this->timestamp_start = 0;
        $this->listeners_start = 0;

        $this->timestamp_end = 0;
        $this->listeners_end = 0;

        $this->unique_listeners = 0;

        $this->delta_total = 0;
        $this->delta_negative = 0;
        $this->delta_positive = 0;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Song
     */
    public function getSong(): Song
    {
        return $this->song;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return StationPlaylist|null
     */
    public function getPlaylist(): ?StationPlaylist
    {
        return $this->playlist;
    }

    /**
     * @param StationPlaylist|null $playlist
     */
    public function setPlaylist(StationPlaylist $playlist = null): void
    {
        $this->playlist = $playlist;
    }

    /**
     * @return StationMedia|null
     */
    public function getMedia(): ?StationMedia
    {
        return $this->media;
    }

    /**
     * @param StationMedia|null $media
     */
    public function setMedia(StationMedia $media = null): void
    {
        $this->media = $media;
    }

    /**
     * @return StationRequest|null
     */
    public function getRequest(): ?StationRequest
    {
        return $this->request;
    }

    /**
     * @param StationRequest|null $request
     */
    public function setRequest($request): void
    {
        $this->request = $request;
    }

    /**
     * @return string|null
     */
    public function getAutodjCustomUri(): ?string
    {
        return $this->autodj_custom_uri;
    }

    /**
     * @param string|null $autodj_custom_uri
     */
    public function setAutodjCustomUri(?string $autodj_custom_uri): void
    {
        $this->autodj_custom_uri = $autodj_custom_uri;
    }

    /**
     * @return int|null
     */
    public function getTimestampCued(): ?int
    {
        return $this->timestamp_cued;
    }

    /**
     * @param int|null $timestamp_cued
     */
    public function setTimestampCued($timestamp_cued): void
    {
        $this->timestamp_cued = $timestamp_cued;
    }

    /**
     * @return bool
     */
    public function getSentToAutodj(): bool
    {
        return $this->sent_to_autodj;
    }

    public function sentToAutodj(): void
    {
        $this->sent_to_autodj = true;
    }

    /**
     * @return int
     */
    public function getTimestampStart(): int
    {
        return $this->timestamp_start;
    }

    /**
     * @param int $timestamp_start
     */
    public function setTimestampStart(int $timestamp_start): void
    {
        $this->timestamp_start = $timestamp_start;
    }

    /**
     * @return int|null
     */
    public function getDuration(): ?int
    {
        return $this->duration;
    }

    /**
     * @param int|null $duration
     */
    public function setDuration($duration): void
    {
        $this->duration = $duration;
    }

    /**
     * @return int|null
     */
    public function getListenersStart(): ?int
    {
        return $this->listeners_start;
    }

    /**
     * @param int|null $listeners_start
     */
    public function setListenersStart($listeners_start): void
    {
        $this->listeners_start = $listeners_start;
    }

    /**
     * @return int
     */
    public function getTimestampEnd(): int
    {
        return $this->timestamp_end;
    }

    /**
     * @param int $timestamp_end
     */
    public function setTimestampEnd(int $timestamp_end): void
    {
        $this->timestamp_end = $timestamp_end;
    }

    /**
     * @return int
     */
    public function getTimestamp(): int
    {
        return (int)$this->timestamp_start;
    }

    /**
     * @return int|null
     */
    public function getListenersEnd(): ?int
    {
        return $this->listeners_end;
    }

    /**
     * @param int|null $listeners_end
     */
    public function setListenersEnd($listeners_end): void
    {
        $this->listeners_end = $listeners_end;
    }

    /**
     * @return int|null
     */
    public function getUniqueListeners(): ?int
    {
        return $this->unique_listeners;
    }

    /**
     * @param int|null $unique_listeners
     */
    public function setUniqueListeners($unique_listeners): void
    {
        $this->unique_listeners = $unique_listeners;
    }

    /**
     * @return int
     */
    public function getListeners(): int
    {
        return (int)$this->listeners_start;
    }

    /**
     * @return int
     */
    public function getDeltaTotal(): int
    {
        return $this->delta_total;
    }

    /**
     * @param int $delta_total
     */
    public function setDeltaTotal(int $delta_total): void
    {
        $this->delta_total = $delta_total;
    }

    /**
     * @return int
     */
    public function getDeltaPositive(): int
    {
        return $this->delta_positive;
    }

    /**
     * @param int $delta_positive
     */
    public function setDeltaPositive(int $delta_positive): void
    {
        $this->delta_positive = $delta_positive;
    }

    /**
     * @return int
     */
    public function getDeltaNegative(): int
    {
        return $this->delta_negative;
    }

    /**
     * @param int $delta_negative
     */
    public function setDeltaNegative(int $delta_negative): void
    {
        $this->delta_negative = $delta_negative;
    }

    /**
     * @return mixed|null
     */
    public function getDeltaPoints()
    {
        return $this->delta_points;
    }

    /**
     * @param mixed $delta_point
     */
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

    /**
     * @param Api\SongHistory $response
     * @param \App\ApiUtilities $api
     * @param UriInterface|null $base_url
     * @return Api\SongHistory
     */
    public function api(Api\SongHistory $response, \App\ApiUtilities $api, UriInterface $base_url = null)
    {
        $response->sh_id = (int)$this->id;
        $response->played_at = (int)$this->timestamp_start;
        $response->duration = (int)$this->duration;
        $response->is_request = $this->request !== null;

        if ($this->playlist instanceof StationPlaylist) {
            $response->playlist = $this->playlist->getName();
        } else {
            $response->playlist = '';
        }

        if ($response instanceof Api\DetailedSongHistory) {
            $response->listeners_start = (int)$this->listeners_start;
            $response->listeners_end = (int)$this->listeners_end;
            $response->delta_total = (int)$this->delta_total;
        }

        if ($response instanceof Api\QueuedSong) {
            $response->cued_at = (int)$this->timestamp_cued;
            $response->autodj_custom_uri = $this->autodj_custom_uri;
        }

        $response->song = ($this->media)
            ? $this->media->api($api, $base_url)
            : $this->song->api($api, $base_url);

        return $response;
    }
}
