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
    public function __construct()
    {
        $this->created = time();
        $this->updated = time();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     */
    protected $id;

    /** @Column(name="hash", type="string", length=50) */
    protected $hash;

    /** @Column(name="created_timestamp", type="integer") */
    protected $created;

    /** @Column(name="timestamp", type="integer") */
    protected $updated;

    /** @Column(name="artist", type="string", length=150, nullable=true) */
    protected $artist;

    /** @Column(name="title", type="string", length=150, nullable=true) */
    protected $title;

    /** @Column(name="web_url", type="string", length=255, nullable=true) */
    protected $web_url;

    /** @Column(name="image_url", type="string", length=255, nullable=true) */
    protected $image_url;

    /** @Column(name="download_url", type="string", length=255, nullable=true) */
    protected $download_url;

    /**
     * Static Functions
     */

    public static function match(Song $song, $force_lookup = false)
    {
        $record = self::getRepository()->findOneBy(array('hash' => $song->id));
        if ($record instanceof self)
            return $record;

        return null;
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
        return array(
            'id'        => $result['id'],
            'created'   => round($result['timestamp']),
            'updated'   => time(),
            'artist'    => $result['artist']['name'],
            'title'     => $result['title'],
            'web_url'   => $result['link'],
            'image_url' => $result['download']['art'],
            'download_url' => $result['download']['mp3'],
        );
    }

    public static function getIds()
    {
        $em = self::getEntityManager();
        $ids_raw = $em->createQuery('SELECT se.id, se.hash FROM '.__CLASS__.' se')->getArrayResult();

        $ids = array();
        foreach($ids_raw as $row)
            $ids[$row['id']] = $row['hash'];

        return $ids;
    }
}