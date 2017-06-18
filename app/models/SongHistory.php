<?php
namespace Entity;

/**
 * @Table(name="song_history", indexes={
 *   @index(name="sort_idx", columns={"timestamp_start"}),
 * })
 * @Entity(repositoryClass="Entity\Repository\SongHistoryRepository")
 */
class SongHistory extends \App\Doctrine\Entity
{
    public function __construct()
    {
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
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /** @Column(name="song_id", type="string", length=50) */
    protected $song_id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="playlist_id", type="integer", nullable=true) */
    protected $playlist_id;

    /** @Column(name="media_id", type="integer", nullable=true) */
    protected $media_id;

    /** @Column(name="request_id", type="integer", nullable=true) */
    protected $request_id;

    /** @Column(name="timestamp_cued", type="integer", nullable=true) */
    protected $timestamp_cued;

    /** @Column(name="timestamp_start", type="integer") */
    protected $timestamp_start;

    public function getTimestamp()
    {
        return $this->timestamp_start;
    }

    /** @Column(name="duration", type="integer", nullable=true) */
    protected $duration;

    /** @Column(name="listeners_start", type="integer", nullable=true) */
    protected $listeners_start;

    public function getListeners()
    {
        return $this->listeners_start;
    }

    /** @Column(name="timestamp_end", type="integer") */
    protected $timestamp_end;

    /** @Column(name="listeners_end", type="smallint", nullable=true) */
    protected $listeners_end;

    /** @Column(name="unique_listeners", type="smallint", nullable=true) */
    protected $unique_listeners;

    /** @Column(name="delta_total", type="smallint") */
    protected $delta_total;

    /** @Column(name="delta_positive", type="smallint") */
    protected $delta_positive;

    /** @Column(name="delta_negative", type="smallint") */
    protected $delta_negative;

    /** @Column(name="delta_points", type="json_array", nullable=true) */
    protected $delta_points;

    /**
     * @ManyToOne(targetEntity="Song", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $song;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToOne(targetEntity="StationPlaylist")
     * @JoinColumns({
     *   @JoinColumn(name="playlist_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $playlist;

    /**
     * @OneToOne(targetEntity="StationRequest")
     * @JoinColumns({
     *   @JoinColumn(name="request_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $request;

    /**
     * @ManyToOne(targetEntity="StationMedia")
     * @JoinColumns({
     *   @JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $media;

    /**
     * @return Api\SongHistory
     */
    public function api()
    {
        $response = new Api\SongHistory;
        $response->sh_id = (int)$this->id;
        $response->played_at = (int)$this->timestamp_start;
        $response->duration = (int)$this->duration;
        $response->is_request = (bool)(!empty($this->request_id));
        $response->song = $this->song->api();
        return $response;
    }
}