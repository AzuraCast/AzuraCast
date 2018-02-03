<?php
namespace Entity;

/**
 * @Table(name="song_history", indexes={
 *   @index(name="sort_idx", columns={"timestamp_start"}),
 * })
 * @Entity(repositoryClass="Entity\Repository\SongHistoryRepository")
 */
class SongHistory
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="song_id", type="string", length=50)
     * @var string
     */
    protected $song_id;

    /**
     * @ManyToOne(targetEntity="Song", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Song
     */
    protected $song;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="playlist_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $playlist_id;

    /**
     * @ManyToOne(targetEntity="StationPlaylist")
     * @JoinColumns({
     *   @JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationPlaylist|null
     */
    protected $playlist;

    /**
     * @Column(name="media_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $media_id;

    /**
     * @ManyToOne(targetEntity="StationMedia")
     * @JoinColumns({
     *   @JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationMedia|null
     */
    protected $media;

    /**
     * @Column(name="request_id", type="integer", nullable=true)
     * @var int|null
     */
    protected $request_id;

    /**
     * @OneToOne(targetEntity="StationRequest")
     * @JoinColumns({
     *   @JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StationRequest|null
     */
    protected $request;

    /**
     * @Column(name="timestamp_cued", type="integer", nullable=true)
     * @var int|null
     */
    protected $timestamp_cued;

    /**
     * @Column(name="sent_to_autodj", type="boolean")
     * @var bool
     */
    protected $sent_to_autodj;

    /**
     * @Column(name="timestamp_start", type="integer")
     * @var int
     */
    protected $timestamp_start;

    /**
     * @Column(name="duration", type="integer", nullable=true)
     * @var int|null
     */
    protected $duration;

    /**
     * @Column(name="listeners_start", type="integer", nullable=true)
     * @var int|null
     */
    protected $listeners_start;

    /**
     * @Column(name="timestamp_end", type="integer")
     * @var int
     */
    protected $timestamp_end;

    /**
     * @Column(name="listeners_end", type="smallint", nullable=true)
     * @var int|null
     */
    protected $listeners_end;

    /**
     * @Column(name="unique_listeners", type="smallint", nullable=true)
     * @var int|null
     */
    protected $unique_listeners;

    /**
     * @Column(name="delta_total", type="smallint")
     * @var int
     */
    protected $delta_total;

    /**
     * @Column(name="delta_positive", type="smallint")
     * @var int
     */
    protected $delta_positive;

    /**
     * @Column(name="delta_negative", type="smallint")
     * @var int
     */
    protected $delta_negative;

    /**
     * @Column(name="delta_points", type="json_array", nullable=true)
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
    public function getPlaylist()
    {
        return $this->playlist;
    }

    /**
     * @param StationPlaylist|null $playlist
     */
    public function setPlaylist(StationPlaylist $playlist = null)
    {
        $this->playlist = $playlist;
    }

    /**
     * @return StationMedia|null
     */
    public function getMedia()
    {
        return $this->media;
    }

    /**
     * @param StationMedia|null $media
     */
    public function setMedia(StationMedia $media = null)
    {
        $this->media = $media;
    }

    /**
     * @return StationRequest|null
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param StationRequest|null $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return int|null
     */
    public function getTimestampCued()
    {
        return $this->timestamp_cued;
    }

    /**
     * @param int|null $timestamp_cued
     */
    public function setTimestampCued($timestamp_cued)
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

    public function sentToAutodj()
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
    public function setTimestampStart(int $timestamp_start)
    {
        $this->timestamp_start = $timestamp_start;
    }

    /**
     * @return int|null
     */
    public function getDuration()
    {
        return $this->duration;
    }

    /**
     * @param int|null $duration
     */
    public function setDuration($duration)
    {
        $this->duration = $duration;
    }

    /**
     * @return int|null
     */
    public function getListenersStart()
    {
        return $this->listeners_start;
    }

    /**
     * @param int|null $listeners_start
     */
    public function setListenersStart($listeners_start)
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
    public function setTimestampEnd(int $timestamp_end)
    {
        $this->timestamp_end = $timestamp_end;
    }

    public function getTimestamp(): int
    {
        return (int)$this->timestamp_start;
    }

    /**
     * @return int|null
     */
    public function getListenersEnd()
    {
        return $this->listeners_end;
    }

    /**
     * @param int|null $listeners_end
     */
    public function setListenersEnd($listeners_end)
    {
        $this->listeners_end = $listeners_end;
    }

    /**
     * @return int|null
     */
    public function getUniqueListeners()
    {
        return $this->unique_listeners;
    }

    /**
     * @param int|null $unique_listeners
     */
    public function setUniqueListeners($unique_listeners)
    {
        $this->unique_listeners = $unique_listeners;
    }

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
    public function setDeltaTotal(int $delta_total)
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
    public function setDeltaPositive(int $delta_positive)
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
    public function setDeltaNegative(int $delta_negative)
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
     * @param $delta_point
     */
    public function addDeltaPoint($delta_point)
    {
        $delta_points = (array)$this->delta_points;
        $delta_points[] = $delta_point;
        $this->delta_points = $delta_points;
    }

    /**
     * @return Api\SongHistory|Api\NowPlayingCurrentSong
     */
    public function api(\App\Url $url, $now_playing = false)
    {
        $response = ($now_playing) ? new Api\NowPlayingCurrentSong : new Api\SongHistory;
        $response->sh_id = (int)$this->id;
        $response->played_at = (int)$this->timestamp_start;
        $response->duration = (int)$this->duration;
        $response->is_request = (bool)(!empty($this->request_id));

        $response->song = ($this->media)
            ? $this->media->api($url)
            : $this->song->api();

        return $response;
    }
}