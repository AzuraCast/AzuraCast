<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_history", indexes={
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongHistory extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->timestamp = time();

        $this->score_likes = 0;
        $this->score_dislikes = 0;
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

    /** @Column(name="stream_id", type="integer", nullable=true) */
    protected $stream_id;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="listeners", type="integer", nullable=true) */
    protected $listeners;

    /** @Column(name="score_likes", type="integer") */
    protected $score_likes;

    /** @Column(name="score_dislikes", type="integer") */
    protected $score_dislikes;

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
     * @ManyToOne(targetEntity="StationStream", inversedBy="history")
     * @JoinColumns({
     *   @JoinColumn(name="stream_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    protected $stream;

    public function like()
    {
        return $this->vote(1);
    }
    public function dislike()
    {
        return $this->vote(0-1);
    }

    public function vote($value)
    {
        $timestamp_threshold = time() - 60*60;

        if ($this->timestamp >= $timestamp_threshold)
        {
            $record = SongVote::getExistingVote($this->song, $this->station);

            if ($record instanceof SongVote)
                $this->clearVote($record);

            if ($value > 0)
                $this->score_likes += 1;
            else
                $this->score_dislikes += 1;

            $this->save();

            // Create new song vote record.
            $vote = new SongVote;
            $vote->song = $this->song;
            $vote->station = $this->station;
            $vote->vote = $value;
            $vote->save();

            return true;
        }

        return false;
    }

    public function clearVote(SongVote $vote = null)
    {
        if ($vote === null)
            $vote = SongVote::getExistingVote($this->song, $this->station);

        if ($vote instanceof SongVote)
        {
            if ($vote->vote > 0)
                $this->score_likes -= 1;
            else
                $this->score_dislikes -= 1;
            $this->save();

            $vote->delete();

            return true;
        }

        return false;
    }

    /**
     * Static Functions
     */

    public static function register(Song $song, Station $station, StationStream $stream, $np)
    {
        // Check to ensure no match with most recent song.
        try
        {
            $em = self::getEntityManager();
            $last_song_id = $em->createQuery('SELECT sh.song_id FROM '.__CLASS__.' sh
                WHERE sh.station_id = :station_id AND sh.stream_id = :stream_id
                ORDER BY sh.timestamp DESC')
                ->setMaxResults(1)
                ->setParameter('station_id', $station->id)
                ->setParameter('stream_id', $stream->id)
                ->getSingleScalarResult();
        }
        catch(\Exception $e)
        {
            $last_song_id = NULL;
        }

        if ($last_song_id != $song->id)
        {
            $sh = new self;
            $sh->song = $song;
            $sh->station = $station;
            $sh->stream = $stream;

            $sh->listeners = (int)$np['listeners']['current'];
            $sh->save();

            return $sh;
        }

        return NULL;
    }
}