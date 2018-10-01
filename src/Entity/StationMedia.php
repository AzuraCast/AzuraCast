<?php

namespace App\Entity;

use App\Radio\Backend\Liquidsoap;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * @Table(name="station_media", indexes={
 *   @index(name="search_idx", columns={"title", "artist", "album"})
 * }, uniqueConstraints={
 *   @UniqueConstraint(name="path_unique_idx", columns={"path", "station_id"})
 * })
 * @Entity(repositoryClass="App\Entity\Repository\StationMediaRepository")
 * @HasLifecycleCallbacks
 */
class StationMedia
{
    use Traits\UniqueId, Traits\TruncateStrings;

    /**
     * @Column(name="id", type="integer")
     * @Id
     * @GeneratedValue(strategy="IDENTITY")
     * @var int
     */
    protected $id;

    /**
     * @Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ManyToOne(targetEntity="Station", inversedBy="media")
     * @JoinColumns({
     *   @JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @Column(name="song_id", type="string", length=50, nullable=true)
     * @var int|null
     */
    protected $song_id;

    /**
     * @ManyToOne(targetEntity="Song")
     * @JoinColumns({
     *   @JoinColumn(name="song_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     * @var Song|null
     */
    protected $song;

    /**
     * @Column(name="title", type="string", length=200, nullable=true)
     * @var string|null
     */
    protected $title;

    /**
     * @Column(name="artist", type="string", length=200, nullable=true)
     * @var string|null
     */
    protected $artist;

    /**
     * @Column(name="album", type="string", length=200, nullable=true)
     * @var string|null
     */
    protected $album;

    /**
     * @Column(name="lyrics", type="text", nullable=true)
     * @var string|null
     */
    protected $lyrics;

    /**
     * @OneToOne(targetEntity="StationMediaArt", mappedBy="media", cascade={"persist"})
     * @var StationMediaArt
     */
    protected $art;

    /**
     * @Column(name="isrc", type="string", length=15, nullable=true)
     * @var string|null The track ISRC (International Standard Recording Code), used for licensing purposes.
     */
    protected $isrc;

    /**
     * @Column(name="length", type="integer")
     * @var int
     */
    protected $length;

    /**
     * @Column(name="length_text", type="string", length=10, nullable=true)
     * @var string|null
     */
    protected $length_text;

    /**
     * @Column(name="path", type="string", length=500, nullable=true)
     * @var string|null
     */
    protected $path;

    /**
     * @Column(name="mtime", type="integer", nullable=true)
     * @var int|null
     */
    protected $mtime;

    /**
     * @Column(name="fade_overlap", type="decimal", precision=3, scale=1, nullable=true)
     * @var float|null
     */
    protected $fade_overlap;

    /**
     * @Column(name="fade_in", type="decimal", precision=3, scale=1, nullable=true)
     * @var float|null
     */
    protected $fade_in;

    /**
     * @Column(name="fade_out", type="decimal", precision=3, scale=1, nullable=true)
     * @var float|null
     */
    protected $fade_out;

    /**
     * @Column(name="cue_in", type="decimal", precision=5, scale=1, nullable=true)
     * @var float|null
     */
    protected $cue_in;

    /**
     * @Column(name="cue_out", type="decimal", precision=5, scale=1, nullable=true)
     * @var float|null
     */
    protected $cue_out;

    /**
     * @OneToMany(targetEntity="StationPlaylistMedia", mappedBy="media")
     * @var Collection
     */
    protected $playlist_items;

    /**
     * @OneToMany(targetEntity="StationMediaCustomField", mappedBy="media")
     * @var Collection
     */
    protected $custom_fields;

    public function __construct(Station $station, string $path)
    {
        $this->station = $station;
        $this->path = $path;

        $this->length = 0;
        $this->length_text = '0:00';

        $this->mtime = 0;

        $this->playlist_items = new ArrayCollection;
        $this->custom_fields = new ArrayCollection;
    }

    /**
     * @return int
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * @return Station
     */
    public function getStation(): Station
    {
        return $this->station;
    }

    /**
     * @return Song|null
     */
    public function getSong(): ?Song
    {
        return $this->song;
    }

    /**
     * @param Song|null $song
     */
    public function setSong(Song $song = null)
    {
        $this->song = $song;
    }

    /**
     * @return null|string
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param null|string $title
     */
    public function setTitle(string $title = null)
    {
        $this->title = $this->_truncateString($title, 200);
    }

    /**
     * @return null|string
     */
    public function getArtist(): ?string
    {
        return $this->artist;
    }

    /**
     * @param null|string $artist
     */
    public function setArtist(string $artist = null)
    {
        $this->artist = $this->_truncateString($artist, 200);
    }

    /**
     * @return null|string
     */
    public function getAlbum(): ?string
    {
        return $this->album;
    }

    /**
     * @param null|string $album
     */
    public function setAlbum(string $album = null)
    {
        $this->album = $this->_truncateString($album, 200);
    }

    /**
     * @return null|string
     */
    public function getLyrics()
    {
        return $this->lyrics;
    }

    /**
     * @param null|string $lyrics
     */
    public function setLyrics($lyrics)
    {
        $this->lyrics = $lyrics;
    }

    /**
     * @return null|resource
     */
    public function getArt()
    {
        if ($this->art instanceof StationMediaArt) {
            return $this->art->getArt();
        }
        return null;
    }

    /**
     * @param resource $source_image_path A GD image manipulation resource.
     * @return bool
     */
    public function setArt($source_gd_image = null)
    {
        if (!($this->art instanceof StationMediaArt)) {
            $this->art = new StationMediaArt($this);
        }

        return $this->art->setArt($source_gd_image);
    }

    /**
     * @return null|string
     */
    public function getIsrc(): ?string
    {
        return $this->isrc;
    }

    /**
     * @param null|string $isrc
     */
    public function setIsrc(string $isrc = null)
    {
        $this->isrc = $isrc;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * @param $length
     */
    public function setLength($length)
    {
        $length_min = floor($length / 60);
        $length_sec = $length % 60;

        $this->length = round($length);
        $this->length_text = $length_min . ':' . str_pad($length_sec, 2, '0', STR_PAD_LEFT);
    }

    /**
     * @return null|string
     */
    public function getLengthText(): ?string
    {
        return $this->length_text;
    }

    /**
     * @param null|string $length_text
     */
    public function setLengthText(string $length_text = null)
    {
        $this->length_text = $length_text;
    }

    /**
     * @return null|string
     */
    public function getPath(): ?string
    {
        return $this->path;
    }

    /**
     * @param null|string $path
     */
    public function setPath(string $path = null)
    {
        $this->path = $path;
    }

    /**
     * @return int|null
     */
    public function getMtime(): ?int
    {
        return $this->mtime;
    }

    /**
     * @param int|null $mtime
     */
    public function setMtime(int $mtime = null)
    {
        $this->mtime = $mtime;
    }

    /**
     * @return float|null
     */
    public function getFadeOverlap(): ?float
    {
        return $this->fade_overlap;
    }

    /**
     * @param float|null $fade_overlap
     */
    public function setFadeOverlap(float $fade_overlap = null)
    {
        $this->fade_overlap = $fade_overlap;
    }

    /**
     * @return float|null
     */
    public function getFadeIn(): ?float
    {
        return $this->fade_in;
    }

    /**
     * @param float|null $fade_in
     */
    public function setFadeIn(float $fade_in = null)
    {
        $this->fade_in = $fade_in;
    }

    /**
     * @return float|null
     */
    public function getFadeOut(): ?float
    {
        return $this->fade_out;
    }

    /**
     * @param float|null $fade_out
     */
    public function setFadeOut(float $fade_out = null)
    {
        $this->fade_out = $fade_out;
    }

    /**
     * @return float|null
     */
    public function getCueIn(): ?float
    {
        return $this->cue_in;
    }

    /**
     * @param float|null $cue_in
     */
    public function setCueIn(float $cue_in = null)
    {
        $this->cue_in = $cue_in;
    }

    /**
     * @return float|null
     */
    public function getCueOut(): ?float
    {
        return $this->cue_out;
    }

    /**
     * @param float|null $cue_out
     */
    public function setCueOut(float $cue_out = null)
    {
        $this->cue_out = $cue_out;
    }

    /**
     * Get the length with cue-in and cue-out points included.
     *
     * @return int
     */
    public function getCalculatedLength()
    {
        $length = (int)$this->length;

        if ((int)$this->cue_out > 0) {
            $length_removed = $length - (int)$this->cue_out;
            $length -= $length_removed;
        }
        if ((int)$this->cue_in > 0) {
            $length -= $this->cue_in;
        }

        return $length;
    }

    /**
     * @return Collection
     */
    public function getPlaylistItems(): Collection
    {
        return $this->playlist_items;
    }

    public function getItemForPlaylist(StationPlaylist $playlist): ?StationPlaylistMedia
    {
        $item = $this->playlist_items->filter(function($spm) use ($playlist) {
            /** @var StationPlaylistMedia $spm */
            return ($spm->getPlaylist()->getId() == $playlist->getId());
        });

        return $item->first() ?? null;
    }

    /**
     * @return Collection
     */
    public function getCustomFields(): Collection
    {
        return $this->custom_fields;
    }

    /**
     * @param Collection $custom_fields
     */
    public function setCustomFields(Collection $custom_fields): void
    {
        $this->custom_fields = $custom_fields;
    }

    /**
     * Assemble a list of annotations for LiquidSoap.
     *
     * Liquidsoap expects a string similar to:
     *     annotate:type="song",album="$ALBUM",display_desc="$FULLSHOWNAME",
     *     liq_start_next="2.5",liq_fade_in="3.5",liq_fade_out="3.5":$SONGPATH
     *
     * @return array
     */
    public function getAnnotations(): array
    {
        $annotations = [];
        $annotation_types = [
            'title' => 'title',
            'artist' => 'artist',
            'fade_overlap' => 'liq_start_next',
            'fade_in' => 'liq_fade_in',
            'fade_out' => 'liq_fade_out',
            'cue_in' => 'liq_cue_in',
            'cue_out' => 'liq_cue_out',
        ];

        foreach ($annotation_types as $annotation_property => $annotation_name) {
            if ($this->$annotation_property !== null) {
                $prop = $this->$annotation_property;
                $prop = mb_convert_encoding($prop, "UTF-8");
                $prop = str_replace(['"', "\n", "\t", "\r"], ["'", '', '', ''], $prop);

                if ($annotation_property === 'cue_out' && $prop < 0) {
                    $prop = max(0, $this->getLength() - abs($prop));
                }

                // Convert Liquidsoap-specific annotations to floats.
                if ('liq' === substr($annotation_name, 0, 3)) {
                    $prop = Liquidsoap::toFloat($prop);
                }

                $annotations[$annotation_property] = $annotation_name . '="' . $prop . '"';
            }
        }

        return $annotations;
    }

    /**
     * Process metadata information from media file.
     *
     * @param bool $force
     * @return array|bool
     *   - Array containing song information, if one is detected and needs updating
     *   - False if information was not updated
     */
    public function loadFromFile($force = false)
    {
        if (empty($this->path)) {
            return false;
        }

        $media_base_dir = $this->station->getRadioMediaDir();
        $media_path = $media_base_dir . '/' . $this->path;

        $path_parts = pathinfo($media_path);

        // Only update metadata if the file has been updated.
        $media_mtime = filemtime($media_path);

        // Check for a hash mismatch.
        $expected_song_hash = Song::getSongHash([
            'artist' => $this->artist,
            'title' => $this->title,
        ]);

        if ($media_mtime > $this->mtime
            || null === $this->song_id
            || $this->song_id != $expected_song_hash
            || $force) {

            $this->mtime = $media_mtime;

            // Load metadata from supported files.
            $id3 = new \getID3();

            $id3->option_md5_data = true;
            $id3->option_md5_data_source = true;
            $id3->encoding = 'UTF-8';

            $file_info = $id3->analyze($media_path);

            if (empty($file_info['error'])) {
                $this->setLength($file_info['playtime_seconds']);

                $tags_to_set = ['title', 'artist', 'album'];
                if (!empty($file_info['tags'])) {
                    foreach ($file_info['tags'] as $tag_type => $tag_data) {
                        foreach ($tags_to_set as $tag) {
                            if (!empty($tag_data[$tag][0])) {
                                $this->{'set'.ucfirst($tag)}(mb_convert_encoding($tag_data[$tag][0], "UTF-8"));
                            }
                        }

                        if (!empty($tag_data['unsynchronized_lyric'][0])) {
                            $this->setLyrics($tag_data['unsynchronized_lyric'][0]);
                        }
                    }
                }

                if (!empty($file_info['comments']['picture'][0])) {
                    $picture = $file_info['comments']['picture'][0];
                    $this->setArt(imagecreatefromstring($picture['data']));
                }
            }

            // Attempt to derive title and artist from filename.
            if (empty($this->title)) {
                $filename = str_replace('_', ' ', $path_parts['filename']);

                $string_parts = explode('-', $filename);

                // If not normally delimited, return "text" only.
                if (count($string_parts) == 1) {
                    $this->setTitle(trim($filename));
                    $this->setArtist('');
                } else {
                    $this->setTitle(trim(array_pop($string_parts)));
                    $this->setArtist(trim(implode('-', $string_parts)));
                }
            }

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

        if (is_resource($this->art)) {
            $tag_data['attached_picture'][0] = [
                'data' => stream_get_contents($this->art),
                'picturetypeid' => 'image/jpeg',
                'mime' => 'image/jpeg',
            ];
            $tag_data['comments']['picture'][0] = $tag_data['attached_picture'][0];
        }

        $tagwriter->tag_data = $tag_data;

        // write tags
        if ($tagwriter->WriteTags()) {
            $this->mtime = time();
            return true;
        }

        return false;
    }

    public function getFullPath()
    {
        $media_base_dir = $this->station->getRadioMediaDir();

        return $media_base_dir . '/' . $this->path;
    }

    /**
     * Indicates whether this media is a part of any "requestable" playlists.
     *
     * @return bool
     */
    public function isRequestable(): bool
    {
        $playlists = $this->getPlaylistItems();
        foreach($playlists as $playlist_item) {
            $playlist = $playlist_item->getPlaylist();
            /** @var StationPlaylist $playlist */
            if ($playlist->isRequestable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @return Api\Song
     */
    public function api(\App\ApiUtilities $api_utils): Api\Song
    {
        $response = new Api\Song;
        $response->id = (string)$this->song_id;
        $response->text = $this->artist . ' - ' . $this->title;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;

        $response->album = (string)$this->album;
        $response->lyrics = (string)$this->lyrics;

        $response->art = $api_utils->getAlbumArtUrl($this->station_id, $this->unique_id);
        $response->custom_fields = $api_utils->getCustomFields($this->id);

        return $response;
    }
}
