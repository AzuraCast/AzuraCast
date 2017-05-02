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
        $this->timestamp_start = time();
        $this->listeners_start = 0;

        $this->timestamp_end = 0;
        $this->listeners_end = 0;

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

    /** @Column(name="timestamp_start", type="integer") */
    protected $timestamp_start;

    public function getTimestamp()
    {
        return $this->timestamp_start;
    }

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
}