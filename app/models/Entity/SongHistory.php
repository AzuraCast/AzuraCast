<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_history", indexes={
 *   @index(name="sort_idx", columns={"timestamp_start"}),
 * })
 * @Entity
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

    /** @Column(name="listeners_start", type="integer", nullable=true) */
    protected $listeners_start;

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

    /** @Column(name="delta_points", type="json", nullable=true) */
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
     * Static Functions
     */

    public static function register(Song $song, Station $station, StationStream $stream, $np)
    {
        $em = self::getEntityManager();

        // Pull the most recent history item for this station.
        $last_sh = $em->createQuery('SELECT sh FROM '.__CLASS__.' sh
            WHERE sh.station_id = :station_id
            ORDER BY sh.timestamp DESC')
            ->setParameter('station_id', $station->id)
            ->setMaxResults(1)
            ->getOneOrNullResult();

        $listeners = (int)$np['listeners']['current'];

        if ($last_sh->song_id == $song->id)
        {
            // Updating the existing SongHistory item with a new data point.
            $delta_points = (array)$last_sh->delta_points;
            $delta_points[] = $listeners;

            $last_sh->delta_points = $delta_points;
            $em->persist($last_sh);

            return null;
        }
        else
        {
            // Wrapping up processing on the previous SongHistory item (if present).
            if ($last_sh instanceof self)
            {
                $last_sh->timestamp_end = time();
                $last_sh->listeners_end = $listeners;

                // Calculate "delta" data for previous item, based on all data points.
                $delta_points = (array)$last_sh->delta_points;
                $delta_points[] = $listeners;

                $delta_positive = 0;
                $delta_negative = 0;
                $delta_total = 0;

                for($i = 1; $i < count($delta_points); $i++)
                {
                    $current_delta = $delta_points[$i];
                    $previous_delta = $delta_points[$i-1];

                    $delta_delta = $current_delta - $previous_delta;
                    $delta_total += $delta_delta;

                    if ($delta_delta > 0)
                        $delta_positive += $delta_delta;
                    elseif ($delta_delta < 0)
                        $delta_negative += abs($delta_delta);
                }

                $last_sh->delta_positive = $delta_positive;
                $last_sh->delta_negative = $delta_negative;
                $last_sh->delta_total = $delta_total;
                $em->persist($last_sh);
            }

            // Processing a new SongHistory item.
            $sh = new self;
            $sh->song = $song;
            $sh->station = $station;

            $sh->listeners_start = $listeners;
            $sh->delta_points = array($listeners);

            $em->persist($sh);
            $em->flush();

            return $sh;
        }
    }

    public static function cleanUp()
    {
        $em = self::getEntityManager();

        $threshold = strtotime('-1 month');

        $em->createQuery('DELETE FROM '.__CLASS__.' sh WHERE sh.timestamp <= :threshold')
            ->setParameter('threshold', $threshold)
            ->execute();

        return true;
    }
}