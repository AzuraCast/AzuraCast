<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_external_bronytunes", indexes={
 *   @index(name="search_idx", columns={"hash"}),
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongExternalBronyTunes extends \DF\Doctrine\Entity
{
    /**
     * @Column(name="id", type="integer")
     * @Id
     */
    protected $id;

    /** @Column(name="hash", type="string", length=50) */
    protected $hash;

    /** @Column(name="timestamp", type="integer") */
    protected $timestamp;

    /** @Column(name="artist", type="string", length=150, nullable=true) */
    protected $artist;

    /** @Column(name="title", type="string", length=150, nullable=true) */
    protected $title;

    /** @Column(name="album", type="string", length=150, nullable=true) */
    protected $album;

    /** @Column(name="description", type="text", nullable=true) */
    protected $description;

    /** @Column(name="lyrics", type="text", nullable=true) */
    protected $lyrics;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=255, nullable=true) */
    protected $image_url;

    /** @Column(name="youtube_url", type="string", length=255, nullable=true) */
    protected $youtube_url;

    /** @Column(name="purchase_url", type="string", length=255, nullable=true) */
    protected $purchase_url;

    /**
     * Static Functions
     */

    public static function match(Song $song, $force_lookup = false)
    {
        $record = self::getRepository()->findOneBy(array('hash' => $song->id));
        if ($record instanceof self && $record->timestamp >= $threshold)
            return $record;
        else
            return NULL;
    }

    public static function processRemote($result)
    {
        $song_hash = Song::getSongHash(array(
            'artist'    => $result['artist_name'],
            'title'     => $result['name'],
        ));

        return array(
            'id'        => $result['song_id'],
            'hash'      => $song_hash,
            'timestamp' => time(),
            'artist'    => $result['artist_name'],
            'title'     => $result['name'],
            'album'     => $result['album'],
            'description' => $result['description'],
            'lyrics'    => $result['lyrics'],
            'web_url'   => 'http://bronytunes.com/songs/'.$result['song_id'],
            'image_url' => 'http://bronytunes.com/retrieve_artwork.php?song_id='.$result['song_id'].'&size=256',
            'youtube_url' => ($result['youtube_id']) ? 'http://youtu.be/'.$result['youtube_id'] : '',
            'purchase_url' => $result['purchase_link'],
        );
    }

    public static function getIds()
    {
        $em = self::getEntityManager();
        $ids_raw = $em->createQuery('SELECT sebt.id, sebt.hash FROM '.__CLASS__.' sebt')->getArrayResult();

        $ids = array();
        foreach($ids_raw as $row)
            $ids[$row['id']] = $row['hash'];

        return $ids;
    }

}