<?php
namespace Entity;

use \Doctrine\Common\Collections\ArrayCollection;
use \PVL\MusicManager;

/**
 * @Table(name="archive_song")
 * @Entity
 * @HasLifecycleCallbacks
 */
class ArchiveSong extends \DF\Doctrine\Entity
{
    public function __construct()
    {
        $this->created_at = time();
        $this->year = date('Y');
        $this->track_number = 0;

        $this->genres = new ArrayCollection;
        $this->artists = new ArrayCollection;
    }

    /** @PreDelete */
    public function deleted()
    {
        @unlink($this->art_path);
        @unlink($this->file_path);
    }

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     */
    protected $id;

    /** @Column(name="created_at", type="integer", nullable=true) */
    protected $created_at;

    /** @Column(name="title", type="string", length=300, nullable=true) */
    protected $title;

    /** @Column(name="artist", type="string", length=200, nullable=true) */
    protected $artist;

    public function setArtist($artist)
    {
        $this->artist = $artist;
        $this->artists->clear();

        $artist_parts = explode(',', $artist);
        foreach((array)$artist_parts as $artist_name)
        {
            $artist_name = trim($artist_name);
            if (!empty($artist_name))
            {
                $artist_obj = Artist::getRepository()->findOneByName($artist_name);
                if ($artist_obj instanceof Artist)
                    $this->artists->add($artist_obj);
            }
        }
    }

    /** @Column(name="album", type="string", length=200, nullable=true) */
    protected $album;

    /** @Column(name="track_number", type="smallint", nullable=true) */
    protected $track_number;

    public function setTrackNumber($tn)
    {
        $this->track_number = (int)$tn;
    }

    /** @Column(name="year", type="smallint", nullable=true) */
    protected $year;

    public function setYear($yr)
    {
        $this->year = (int)substr(trim($yr), 0, 4);
    }

    /** @Column(name="file_path", type="string", length=300, nullable=true) */
    protected $file_path;

    public function getExpectedFilePath()
    {
        return implode('/', array(
            MusicManager::MUSIC_DIR,
            $this->getCleanPath($this->artist),
            $this->getCleanPath($this->album),
            $this->getCleanPath($this->title).'.mp3',
        ));
    }
    public function setFilePath($new_path)
    {
        if (!empty($new_path) && file_exists($new_path))
        {
            if ($this->file_path && $new_path != $this->file_path)
                @unlink($this->file_path);

            $this->file_path = $new_path;
        }
    }

    public function getFileUrl()
    {
        return str_replace(MusicManager::MUSIC_DIR, MusicManager::MUSIC_URL, $this->file_path);
    }

    /** @Column(name="art_path", type="string", length=300, nullable=true) */
    protected $art_path;

    public function getExpectedArtPath()
    {
        return implode('/', array(
            MusicManager::ART_DIR,
            $this->getCleanPath($this->artist),
            $this->getCleanPath($this->album),
            $this->getCleanPath($this->title).'.jpg',
        ));
    }
    public function setArtPath($new_path)
    {
        if (!empty($new_path) && file_exists($new_path))
        {
            if ($this->art_path && $new_path != $this->art_path)
                @unlink($this->art_path);

            $this->art_path = $new_path;
        }
    }

    public function getArtUrl()
    {
        return str_replace(MusicManager::ART_DIR, MusicManager::ART_URL, $this->art_path);
    }

    /**
     * @ManyToMany(targetEntity="ArchiveGenre", inversedBy="songs")
     * @JoinTable(name="archive_song_has_genre",
     *      joinColumns={@JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="genre_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $genres;

    public function setGenre($genre)
    {
        $this->genres->clear();

        $genre_parts = explode(',', $genre);
        foreach((array)$genre_parts as $genre_item)
        {
            $genre_item = trim($genre_item);

            if (!empty($genre_item))
            {
                $genre_obj = ArchiveGenre::getRepository()->findOneByName($genre_item);
                if (!($genre_obj instanceof ArchiveGenre))
                {
                    $genre_obj = new ArchiveGenre;
                    $genre_obj->name = $genre_item;
                    $genre_obj->save();
                }

                $this->genres->add($genre_obj);
            }
        }
    }

    public function getGenre()
    {
        $genre_string = array();
        foreach($this->genres as $genre)
            $genre_string[] = $genre->name;

        return implode(', ', $genre_string);
    }

    /**
     * @ManyToMany(targetEntity="Artist", inversedBy="songs")
     * @JoinTable(name="archive_song_has_artist",
     *      joinColumns={@JoinColumn(name="song_id", referencedColumnName="id", onDelete="CASCADE")},
     *      inverseJoinColumns={@JoinColumn(name="artist_id", referencedColumnName="id", onDelete="CASCADE")}
     * )
     */
    protected $artists;

    /**
     * Utility Functions
     */

    public function fixPaths($clean_up = true)
    {
        $paths_to_fix = array(
            'art_path' => $this->getExpectedArtPath(),
            'file_path' => $this->getExpectedFilePath(),
        );

        $paths_changed = false;
        foreach($paths_to_fix as $path_key => $new_path_value)
        {
            $current_path_value = $this->$path_key;

            if (file_exists($current_path_value) && $current_path_value != $new_path_value)
            {
                @mkdir(dirname($new_path_value), 0755, true);
                @rename($current_path_value, $new_path_value);

                $this->$path_key = $new_path_value;
                $paths_changed = true;
            }
        }

        if ($paths_changed && $clean_up)
        {
            MusicManager::cleanUpMainDirectories();
        }
    }

    public function writeToFile()
    {
        MusicManager::writeSongData($this);
    }

    public function getCleanPath($string)
    {
        return preg_replace("/[^A-Za-z0-9_()]/", '', trim(str_replace(' ', '_', $string)));
    }

    /**
     * Static Functions
     */

    public static function getExistingSongHashes()
    {
        $all_songs = self::fetchArray();
        $song_hashes = array();

        foreach($all_songs as $song)
            $song_hashes[] = self::getSongHash($song);

        return $song_hashes;
    }

    public static function getSongHash($song_info)
    {
        return md5($song_info['artist'].' - '.$song_info['album'].' - '.$song_info['title']);
    }
}