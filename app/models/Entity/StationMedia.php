<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;
use \GetId3\GetId3Core as GetId3;

/**
 * @Table(name="station_media", indexes={
 *   @index(name="search_idx", columns={"title", "artist", "album"}),
 *   @index(name="path_idx", columns={"path"})
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
        
        $this->mtime = 0;

        $this->playlists = new ArrayCollection();
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
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

    public function setLength($length)
    {
        $length_min = floor($length / 60);
        $length_sec = $length % 60;

        $this->length = $length;
        $this->length_text = $length_min.':'.str_pad($length_sec, 2, '0', STR_PAD_LEFT);
    }

    /** @Column(name="length_text", type="string", length=10, nullable=true) */
    protected $length_text;

    /** @Column(name="path", type="string", length=255, nullable=true) */
    protected $path;

    /** @Column(name="mtime", type="integer", nullable=true) */
    protected $mtime;

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
     * @ManyToMany(targetEntity="StationPlaylist", inversedBy="playlists")
     * @JoinTable(name="station_playlist_has_media",
     *   joinColumns={@JoinColumn(name="media_id", referencedColumnName="id", onDelete="CASCADE")},
     *   inverseJoinColumns={@JoinColumn(name="playlists_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $playlists;

    /**
     * Process metadata information from media file.
     */
    public function loadFromFile()
    {
        if (empty($this->path))
            return false;
        
        $media_base_dir = $this->station->getRadioMediaDir();
        $media_path = $media_base_dir.'/'.$this->path;

        // Only update metadata if the file has been updated.
        $media_mtime = filemtime($media_path);
        if ($media_mtime >= $this->mtime)
        {
            // Load metadata from MP3 file.
            $id3 = new GetId3();

            $file_info = $id3->setOptionMD5Data(true)
                ->setOptionMD5DataSource(true)
                ->setEncoding('UTF-8')
                ->analyze($media_path);

            if (isset($file_info['error']))
                \App\Debug::log('Error processing file: '.$file_info['error']);

            $this->setLength($file_info['playtime_seconds']);

            if (!empty($file_info['tags']['id3v2']['title'][0]))
            {
                $id3_tags = $file_info['tags']['id3v2'];

                $this->title = $id3_tags['title'][0];
                $this->artist = $id3_tags['artist'][0];
                $this->album = $id3_tags['album'][0];
            }
            elseif (!empty($file_info['tags']['id3v1']['title'][0]))
            {
                $id3_tags = $file_info['tags']['id3v1'];

                $this->title = $id3_tags['title'][0];
                $this->artist = $id3_tags['artist'][0];
                $this->album = $id3_tags['album'][0];
            }
            else
            {
                $path_parts = pathinfo($media_path);
                $this->title = $path_parts['filename'];
            }

            // Associate song from new record.
            $this->song = Song::getOrCreate(array(
                'artist'    => $this->artist,
                'title'     => $this->title,
            ));
            
            $this->mtime = $media_mtime;
            return true;
        }
        
        return false;
    }

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

    public static function getOrCreate(Station $station, $path)
    {
        $short_path = ltrim(str_replace($station->getRadioMediaDir(), '', $path), '/');

        $record = self::getRepository()->findOneBy(['station_id' => $station->id, 'path' => $short_path]);

        if (!($record instanceof self))
        {
            $record = new self;
            $record->station = $station;
            $record->path = $short_path;
        }

        $record->loadFromFile();
        return $record;
    }
}