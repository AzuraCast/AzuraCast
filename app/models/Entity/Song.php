<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="songs", indexes={
 *   @index(name="search_idx", columns={"text", "artist", "title"})
 * })
 * @Entity(repositoryClass="SongRepository")
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

    /** 
     * @OneToMany(targetEntity="SongHistory", mappedBy="song")
     * @OrderBy({"timestamp" = "DESC"})
     */
    protected $history;

    /**
     * Static Functions
     */

    /**
     * @param $song_info
     * @return string
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
        else if (!empty($song_info['artist']))
            $song_text = $song_info['artist'].' - '.$song_info['title'];
        else
            $song_text = $song_info['title'];

        $hash_base = strtolower(preg_replace("/[^A-Za-z0-9]/", '', $song_text));
        return md5($hash_base);
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param $row
     * @return array
     */
    public static function api($row)
    {
        return array(
            'id'        => $row['id'],
            'text'      => $row['text'],
            'artist'    => $row['artist'],
            'title'     => $row['title'],

            'created'   => (int)$row['created'],
            'play_count' => (int)$row['play_count'],
            'last_played' => (int)$row['last_played'],
        );
    }
}

use App\Doctrine\Repository;

class SongRepository extends Repository
{
    /**
     * Return a song by its ID, including resolving merged song IDs.
     *
     * @param $song_hash
     * @return null|object
     */
    public function getById($song_hash)
    {
        $record = $this->find($song_hash);

        if ($record instanceof Song)
        {
            if (!empty($record->merge_song_id))
                return $this->getById($record->merge_song_id);
            else
                return $record;
        }

        return null;
    }

    /**
     * Get a list of all song IDs.
     *
     * @return array
     */
    public function getIds()
    {
        $ids_raw = $this->_em->createQuery('SELECT s.id FROM '.$this->_entityName.' s')
            ->getArrayResult();

        return \Packaged\Helpers\Arrays::ipull($ids_raw, 'id');
    }

    public function getOrCreate($song_info, $is_radio_play = false)
    {
        $song_hash = Song::getSongHash($song_info);

        $obj = $this->getById($song_hash);

        if ($obj instanceof Song)
        {
            if ($is_radio_play)
            {
                $obj->last_played = time();
                $obj->play_count += 1;
            }

            $this->_em->persist($obj);
            $this->_em->flush();

            return $obj;
        }
        else
        {
            if (!is_array($song_info))
                $song_info = array('text' => $song_info);

            $obj = new Song;
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

            $this->_em->persist($obj);
            $this->_em->flush();

            return $obj;
        }
    }
}