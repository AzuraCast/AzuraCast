<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_votes", indexes={
 *   @index(name="search_idx", columns={"ip"}),
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongVote extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->ip = self::getIp();
        $this->timestamp = time();
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

    /** @Column(name="ip", type="string", length=45) */
    protected $ip;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="vote", type="smallint", nullable=true) */
    protected $vote;

    /**
     * @ManyToOne(targetEntity="Song", inversedBy="votes")
     * @JoinColumns({
     *   @JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $song;

    /**
     * @ManyToOne(targetEntity="Station")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * Static Functions
     */

    public static function getIp()
    {
        return $_SERVER['REMOTE_ADDR'];
    }

    public static function getExistingVote(Song $song, Station $station)
    {
        $threshold = time() - 60*15;
        $ip = self::getIp();

        $record = self::getRepository()->findOneBy(array(
            'ip'        => $ip,
            'song_id'   => $song->id,
            'station_id' => $station->id,
        ));

        if ($record instanceof self && $record->timestamp >= $threshold)
            return $record;
        else
            return NULL;
    }

    public static function getScoreForStation(Song $song, Station $station)
    {
        try
        {
            $em = self::getEntityManager();
            $score = $em->createQuery('SELECT SUM(sv.vote) FROM '.__CLASS__.' sv WHERE sv.song_id = :song AND sv.station_id = :station ORDER BY sv.timestamp DESC')
                ->setParameter('song', $song->id)
                ->setParameter('station', $station->id)
                ->getSingleScalarResult();
        }
        catch(\Exception $e)
        {
            $score = 0;
        }

        return $score;
    }

    public static function getScore(Song $song)
    {
        try
        {
            $em = self::getEntityManager();
            $score = $em->createQuery('SELECT SUM(sv.vote) FROM '.__CLASS__.' sv WHERE sv.song_id = :song ORDER BY sv.timestamp DESC')
                ->setParameter('song', $song->id)
                ->getSingleScalarResult();
        }
        catch(\Exception $e)
        {
            $score = 0;
        }

        return $score;
    }

}