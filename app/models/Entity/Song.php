<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="songs")
 * @Entity
 * @HasLifecycleCallbacks
 */
class Song extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->created_at = new \DateTime('NOW');
        $this->rate_likes = 0;
        $this->rate_dislikes = 0;
        $this->rate_score = 0;

        $this->likes = new ArrayCollection;
        $this->dislikes = new ArrayCollection;
        $this->history = new ArrayCollection;
    }

    /** @PrePersist */
    public function preSave()
    {
        $this->id = self::getSongHash($this);
    }

    /**
     * @Column(name="id", type="string", length=50)
     * @Id
     */
    protected $id;

    /** @Column(name="text", type="string", length=150, nullable=true) */
    protected $text;

    /** @Column(name="artist", type="string", length=150, nullable=true) */
    protected $artist;

    /** @Column(name="title", type="string", length=150, nullable=true) */
    protected $title;

    /** @Column(name="created_at", type="datetime", nullable=true) */
    protected $created_at;

    /** @Column(name="rate_likes", type="smallint") */
    protected $rate_likes;

    /** @Column(name="rate_dislikes", type="smallint") */
    protected $rate_dislikes;

    /** @Column(name="rate_score", type="smallint") */
    protected $rate_score;

    /**
     * @ManyToMany(targetEntity="User", inversedBy="liked_songs")
     * @JoinTable(name="song_likes",
     *      joinColumns={@JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")}
     * )
     */
    protected $likes;

    /**
     * @ManyToMany(targetEntity="User", inversedBy="disliked_songs")
     * @JoinTable(name="song_dislikes",
     *      joinColumns={@JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="user_id", referencedColumnName="uid", onDelete="CASCADE")}
     * )
     */
    protected $dislikes;

    /** 
     * @OneToMany(targetEntity="SongHistory", mappedBy="song")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

    public function like(User $user)
    {
        $this->_clearLikes($user);
        $this->likes->add($user);

        $this->updateScore();
        $this->save();
    }

    public function dislike(User $user)
    {
        $this->_clearLikes($user);
        $this->dislikes->add($user);

        $this->updateScore();
        $this->save();
    }

    protected function _clearLikes(User $user)
    {
        $this->likes->removeElement($user);
        $this->dislikes->removeElement($user);
    }

    public function updateScore()
    {
        $this->rate_likes = (int)$this->likes->count();
        $this->rate_dislikes = (int)$this->dislikes->count();
        $this->rate_score = (int)($this->rate_likes - $this->rate_dislikes);
    }

    public function playedOnStation(Station $station)
    {
        // Check to ensure no match with most recent song.
        try
        {
            $em = self::getEntityManager();
            $last_song_id = $em->createQuery('SELECT sh.song_id FROM Entity\SongHistory sh WHERE sh.station_id = :station_id ORDER BY sh.timestamp DESC')
                ->setMaxResults(1)
                ->setParameter('station_id', $station->id)
                ->getSingleScalarResult();
        }
        catch(\Exception $e)
        {
            $last_song_id = NULL;
        }

        if ($last_song_id != $this->id)
        {
            $sh = new SongHistory;
            $sh->song = $this;
            $sh->station = $station;
            $sh->save();

            return $sh;
        }
    }

    /**
     * Static Functions
     */

    public static function getSongHash($song_info)
    {
        // Handle various input types.
        if ($song_info instanceof self)
        {
            $song_info = array(
                'text'  => $song_info->text,
                'artist' => $song_info->artist,
                'title' => $song_info->title,
            );
        }
        elseif (!is_array($song_info))
        {
            $song_info = array(
                'text' => $song_info,
            );
        }

        // Generate hash.
        if ($song_info['text'])
            $song_text = $song_info['text'];
        else
            $song_text = $song_info['artist'].' - '.$song_info['title'];

        $hash_base = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $song_text));
        return md5($hash_base);
    }

    public static function getOrCreate($song_info)
    {
        $song_hash = self::getSongHash($song_info);

        $obj = self::find($song_hash);

        if ($obj instanceof self)
        {
            return $obj;
        }
        else
        {
            if (!is_array($song_info))
                $song_info = array('text' => $song_info);

            $obj = new self;
            $obj->text = $song_info['text'];
            $obj->title = $song_info['title'];
            $obj->artist = $song_info['artist'];
            $obj->save();

            return $obj;
        }
    }

    // Retrieve the API version of the object/array.
    public static function api($row)
    {
        if ($row instanceof self)
            $row = $row->toArray();

        return array(
            'id'        => $row['id'],
            'text'      => $row['text'],
            'artist'    => $row['artist'],
            'title'     => $row['title'],
            'rating'    => array(
                'likes'     => $row['rate_likes'],
                'dislikes'  => $row['rate_dislikes'],
                'score'     => $row['rate_score'],
            ),
        );
    }
}