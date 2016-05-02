<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_media", indexes={
 *   @index(name="search_idx", columns={"title", "artist", "album"})
 * })
 * @Entity
 * @HasLifecycleCallbacks
 */
class StationMedia extends \App\Doctrine\Entity
{
    public function __construct()
    {
        $this->length = 0;
        $this->length_text = '0:00';
        $this->requests = 0;
        $this->newest_request = 0;
    }

    /** @PrePersist */
    public function preSave()
    {
        $this->song = Song::getOrCreate(array(
            'text'      => $this->artist.' - '.$this->title,
            'artist'    => $this->artist,
            'title'     => $this->title,
        ));
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="NONE")
     */
    protected $id;

    /** @Column(name="station_id", type="integer") */
    protected $station_id;

    /** @Column(name="song_id", type="string", length=50, nullable=true) */
    protected $song_id;

    /** @Column(name="title", type="string", length=200) */
    protected $title;

    /** @Column(name="artist", type="string", length=200) */
    protected $artist;

    /** @Column(name="album", type="string", length=200, nullable=true) */
    protected $album;

    /** @Column(name="length", type="smallint") */
    protected $length;

    /** @Column(name="length_text", type="string", length=10, nullable=true) */
    protected $length_text;

    /** @Column(name="requests", type="smallint") */
    protected $requests;

    /** @Column(name="newest_request", type="integer") */
    protected $newest_request;

    public function logRequest()
    {
        $this->requests = (int)$this->requests + 1;
        $this->newest_request = time();

        $record = new StationRequest;
        $record->track = $this;
        $record->station = $this->station;
        $record->save();
    }

    public function setLength($length)
    {
        $length_min = floor($length / 60);
        $length_sec = $length % 60;

        $this->length = $length;
        $this->length_text = $length_min.':'.str_pad($length_sec, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="media")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     */
    protected $station;

    /**
     * @ManyToOne(targetEntity="Song")
     * @JoinColumns({
     *   @JoinColumn(name="song_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     */
    protected $song;

    /**
     * Static Functions
     */

    public static function getRequestable(Station $station)
    {
        $em = self::getEntityManager();

        $requestable = $em->createQuery('SELECT sm FROM '.__CLASS__.' sm WHERE sm.station_id = :station_id ORDER BY sm.artist ASC, sm.title ASC')
            ->setParameter('station_id', $station->id)
            ->getArrayResult();

        return $requestable;
    }

    public static function getByArtist(Station $station, $artist_name)
    {
        $em = self::getEntityManager();

        $requestable = $em->createQuery('SELECT sm FROM '.__CLASS__.' sm WHERE sm.station_id = :station_id AND sm.artist LIKE :artist ORDER BY sm.title ASC')
            ->setParameter('station_id', $station->id)
            ->setParameter('artist', $artist_name)
            ->getArrayResult();

        return $requestable;
    }

    public static function search(Station $station, $query)
    {
        $em = self::getEntityManager();
        $db = $em->getConnection();

        $table_name = $em->getClassMetadata(__CLASS__)->getTableName();

        $stmt = $db->executeQuery('SELECT sm.* FROM '.$db->quoteIdentifier($table_name).' AS sm WHERE sm.station_id = ? AND CONCAT(sm.title, \' \', sm.artist, \' \', sm.album) LIKE ?', array($station->id, '%'.addcslashes($query, "%_").'%'));
        $results = $stmt->fetchAll();

        return $results;
    }
}