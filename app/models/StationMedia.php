<?php
namespace Entity;

use Doctrine\Common\Collections\ArrayCollection;

/**
 * @Table(name="station_media", indexes={
 *   @index(name="search_idx", columns={"title", "artist", "album"})
 * }, uniqueConstraints={
 *   @UniqueConstraint(name="path_unique_idx", columns={"path", "station_id"})
 * })
 * @Entity(repositoryClass="Entity\Repository\StationMediaRepository")
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

    /** @Column(name="title", type="string", length=200, nullable=true) */
    protected $title;

    /** @Column(name="artist", type="string", length=200, nullable=true) */
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
        $this->length_text = $length_min . ':' . str_pad($length_sec, 2, '0', STR_PAD_LEFT);
    }

    /** @Column(name="length_text", type="string", length=10, nullable=true) */
    protected $length_text;

    /** @Column(name="path", type="string", length=255, nullable=true) */
    protected $path;

    public function getFullPath()
    {
        $media_base_dir = $this->station->getRadioMediaDir();

        return $media_base_dir . '/' . $this->path;
    }

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
    public function loadFromFile($force = false)
    {
        if (empty($this->path)) {
            return false;
        }

        $media_base_dir = $this->station->getRadioMediaDir();
        $media_path = $media_base_dir . '/' . $this->path;

        // Only update metadata if the file has been updated.
        $media_mtime = filemtime($media_path);
        if ($media_mtime > $this->mtime || !$this->song || $force) {
            // Load metadata from MP3 file.
            $id3 = new \getID3();

            $id3->option_md5_data = true;
            $id3->option_md5_data_source = true;
            $id3->encoding = 'UTF-8';

            $file_info = $id3->analyze($media_path);

            if (isset($file_info['error'])) {
                throw new \App\Exception($file_info['error'][0]);
            }

            $this->setLength($file_info['playtime_seconds']);

            $tags_to_set = ['title', 'artist', 'album'];

            if (!empty($file_info['tags'])) {
                foreach ($file_info['tags'] as $tag_type => $tag_data) {
                    foreach ($tags_to_set as $tag) {
                        if (!empty($tag_data[$tag][0])) {
                            $this->{$tag} = $tag_data[$tag][0];
                        }
                    }
                }
            }

            if (empty($this->title)) {
                $path_parts = pathinfo($media_path);
                $this->title = $path_parts['filename'];
            }

            $this->mtime = $media_mtime;

            return [
                'artist' => $this->artist,
                'title' => $this->title,
            ];
        }

        return false;
    }

    /**
     * Write modified metadata directly to the file as ID3 information.
     */
    public function writeToFile()
    {
        $getID3 = new \getID3;
        $getID3->setOption(['encoding' => 'UTF8']);

        require_once(APP_INCLUDE_VENDOR . '/james-heinrich/getid3/getid3/write.php');

        $tagwriter = new \getid3_writetags;
        $tagwriter->filename = $this->getFullPath();

        $tagwriter->tagformats = ['id3v1', 'id3v2.3'];
        $tagwriter->overwrite_tags = true;
        $tagwriter->tag_encoding = 'UTF8';
        $tagwriter->remove_other_tags = true;

        $tag_data = [
            'title' => [$this->title],
            'artist' => [$this->artist],
            'album' => [$this->album],
        ];

        $tagwriter->tag_data = $tag_data;

        // write tags
        if ($tagwriter->WriteTags()) {
            $this->mtime = time();

            return true;
        }
    }

    /**
     * Move this media file to the "not-processed" directory.
     */
    public function moveToNotProcessed()
    {
        $old_path = $this->getFullPath();

        $media_base_dir = $this->station->getRadioMediaDir();
        $unprocessed_dir = $media_base_dir . '/not-processed';

        @mkdir($unprocessed_dir);

        $new_path = $unprocessed_dir . '/' . basename($this->path);
        @rename($old_path, $new_path);
    }

    /**
     * Return a list of supported formats.
     *
     * @return array
     */
    public static function getSupportedFormats()
    {
        return ['mp3', 'ogg', 'm4a', 'flac'];
    }
}