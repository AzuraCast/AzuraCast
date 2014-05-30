<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="song_external_eq_beats", indexes={
 *   @index(name="search_idx", columns={"hash"}),
 *   @index(name="sort_idx", columns={"timestamp"}),
 * })
 * @Entity
 */
class SongExternalEqBeats extends \DF\Doctrine\Entity
{
    const SYNC_THRESHOLD = 2592000; // 2592000 = 30 days, 86400 = 1 day

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

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=255, nullable=true) */
    protected $image_url;

    /**
     * Static Functions
     */

    public static function match(Song $song, $force_lookup = false)
    {
        $threshold = time()-self::SYNC_THRESHOLD;

        if (!$force_lookup)
        {
            $record = self::getRepository()->findOneBy(array('hash' => $song->id));
            if ($record instanceof self && $record->timestamp >= $threshold)
                return $record;
        }

        return self::lookUp($song);
    }

    public static function lookUp(Song $song)
    {
        $result = \PVL\Service\EqBeats::fetch($song);

        if ($result)
        {
            $record_data = self::processRemote($result);

            $record = self::find($record_data['id']);
            if (!($record instanceof self))
                $record = new self;

            $record->fromArray($record_data);
            $record->save();

            return $record;
        }

        return NULL;
    }

    public static function processRemote($result)
    {
        $song_hash = Song::getSongHash(array(
            'artist'    => $result['artist']['name'],
            'title'     => $result['title'],
        ));

        return array(
            'id'        => $result['id'],
            'hash'      => $song_hash,
            'timestamp' => time(),
            'artist'    => $result['artist']['name'],
            'title'     => $result['title'],
            'web_url'   => $result['link'],
            'image_url' => $result['download']['art'],
        );
    }

}