<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="songs", indexes={
 *   @index(name="search_idx", columns={"text", "artist", "title"})
 * })
 * @Entity
 * @HasLifecycleCallbacks
 */
class Song extends \App\Doctrine\Entity
{
    const SYNC_THRESHOLD = 604800; // 604800 = 1 week

    public function __construct()
    {
        $this->created = time();
        $this->play_count = 0;
        $this->last_played = 0;

        $this->history = new ArrayCollection;
    }

    /** @PrePersist */
    public function preSave()
    {
        if (empty($this->id))
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

    /** @Column(name="created", type="integer") */
    protected $created;

    /** @Column(name="play_count", type="integer") */
    protected $play_count;

    /** @Column(name="last_played", type="integer") */
    protected $last_played;

    /** @Column(name="score", type="smallint") */
    protected $score;

    /** 
     * @OneToMany(targetEntity="SongHistory", mappedBy="song")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

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
        if (!empty($song_info['text']))
            $song_text = $song_info['text'];
        else
            $song_text = $song_info['artist'].' - '.$song_info['title'];

        $hash_base = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $song_text));
        return md5($hash_base);
    }

    public static function getById($song_hash)
    {
        $record = self::find($song_hash);

        if ($record instanceof self)
        {
            if (!empty($record->merge_song_id))
                return self::getById($record->merge_song_id);
            else
                return $record;
        }

        return null;
    }

    public static function getIds()
    {
        $em = self::getEntityManager();
        $ids_raw = $em->createQuery('SELECT s.id FROM '.__CLASS__.' s')->getArrayResult();

        return \App\Utilities::ipull($ids_raw, 'id');
    }

    public static function getOrCreate($song_info, $is_radio_play = false)
    {
        $song_hash = self::getSongHash($song_info);

        $obj = self::getById($song_hash);

        if ($obj instanceof self)
        {
            if ($is_radio_play)
            {
                $obj->last_played = time();
                $obj->play_count += 1;
            }

            $obj->save();

            return $obj;
        }
        else
        {
            if (!is_array($song_info))
                $song_info = array('text' => $song_info);

            $obj = new self;
            $obj->id = $song_hash;

            if (empty($song_info['text']))
                $song_info['text'] = $song_info['artist'].' - '.$song_info['title'];

            $obj->text = $song_info['text'];
            $obj->title = $song_info['title'];
            $obj->artist = $song_info['artist'];

            if (isset($song_info['image_url']))
                $obj->image_url = $song_info['image_url'];

            if ($is_radio_play)
            {
                $obj->last_played = time();
                $obj->play_count = 1;
            }

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

            'image_url' => $row['image_url'],
            'created'   => (int)$row['created'],
            'play_count' => (int)$row['play_count'],
            'last_played' => (int)$row['last_played'],
            'score'     => (int)$row['score'],
        );
    }
}