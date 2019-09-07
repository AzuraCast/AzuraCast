<?php
namespace App\Entity;

use App\ApiUtilities;
use App\Radio\Backend\Liquidsoap;
use Azura\Normalizer\Annotation\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Psr\Http\Message\UriInterface;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="station_media", indexes={
 *   @ORM\Index(name="search_idx", columns={"title", "artist", "album"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="path_unique_idx", columns={"path", "station_id"})
 * })
 * @ORM\Entity(repositoryClass="App\Entity\Repository\StationMediaRepository")
 *
 * @OA\Schema(type="object")
 */
class StationMedia
{
    use Traits\UniqueId, Traits\TruncateStrings;

    /**
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     *
     * @OA\Property(example=1)
     *
     * @var int
     */
    protected $id;

    /**
     * @ORM\Column(name="station_id", type="integer")
     * @var int
     */
    protected $station_id;

    /**
     * @ORM\ManyToOne(targetEntity="Station", inversedBy="media")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="station_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var Station
     */
    protected $station;

    /**
     * @ORM\Column(name="song_id", type="string", length=50, nullable=true)
     *
     * @OA\Property(example="098F6BCD4621D373CADE4E832627B4F6")
     *
     * @var string|null
     */
    protected $song_id;

    /**
     * @ORM\ManyToOne(targetEntity="Song")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="song_id", referencedColumnName="id", onDelete="SET NULL")
     * })
     * @var Song|null
     */
    protected $song;

    /**
     * @ORM\Column(name="title", type="string", length=200, nullable=true)
     *
     * @OA\Property(example="Test Song")
     *
     * @var string|null The name of the media file's title.
     */
    protected $title;

    /**
     * @ORM\Column(name="artist", type="string", length=200, nullable=true)
     *
     * @OA\Property(example="Test Artist")
     *
     * @var string|null The name of the media file's artist.
     */
    protected $artist;

    /**
     * @ORM\Column(name="album", type="string", length=200, nullable=true)
     *
     * @OA\Property(example="Test Album")
     *
     * @var string|null The name of the media file's album.
     */
    protected $album;

    /**
     * @ORM\Column(name="lyrics", type="text", nullable=true)
     *
     * @OA\Property(example="...Never gonna give you up...")
     *
     * @var string|null Full lyrics of the track, if available.
     */
    protected $lyrics;

    /**
     * @ORM\Column(name="isrc", type="string", length=15, nullable=true)
     *
     * @OA\Property(example="GBARL0600786")
     *
     * @var string|null The track ISRC (International Standard Recording Code), used for licensing purposes.
     */
    protected $isrc;

    /**
     * @ORM\Column(name="length", type="integer")
     *
     * @OA\Property(example=240)
     *
     * @var int The song duration in seconds.
     */
    protected $length;

    /**
     * @ORM\Column(name="length_text", type="string", length=10, nullable=true)
     *
     * @OA\Property(example="4:00")
     *
     * @var string|null The formatted song duration (in mm:ss format)
     */
    protected $length_text;

    /**
     * @ORM\Column(name="path", type="string", length=500, nullable=true)
     *
     * @OA\Property(example="test.mp3")
     *
     * @var string|null The relative path of the media file.
     */
    protected $path;

    /**
     * @ORM\Column(name="mtime", type="integer", nullable=true)
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     *
     * @var int|null The UNIX timestamp when the database was last modified.
     */
    protected $mtime;

    /**
     * @ORM\Column(name="fade_overlap", type="decimal", precision=3, scale=1, nullable=true)
     *
     * @OA\Property(example=2.00)
     *
     * @var float|null The length of time (in seconds) before the next song starts in the fade;
     *                 equivalent to Liquidsoap's "liq_start_next" annotation.
     */
    protected $fade_overlap;

    /**
     * @ORM\Column(name="fade_in", type="decimal", precision=3, scale=1, nullable=true)
     *
     * @OA\Property(example=3.00)
     *
     * @var float|null The length of time (in seconds) to fade in the next track;
     *                 equivalent to Liquidsoap's "liq_fade_in" annotation.
     */
    protected $fade_in;

    /**
     * @ORM\Column(name="fade_out", type="decimal", precision=3, scale=1, nullable=true)
     *
     * @OA\Property(example=3.00)
     *
     * @var float|null The length of time (in seconds) to fade out the previous track;
     *                 equivalent to Liquidsoap's "liq_fade_out" annotation.
     */
    protected $fade_out;

    /**
     * @ORM\Column(name="cue_in", type="decimal", precision=5, scale=1, nullable=true)
     *
     * @OA\Property(example=30.00)
     *
     * @var float|null The length of time (in seconds) from the start of the track to start playing;
     *                 equivalent to Liquidsoap's "liq_cue_in" annotation.
     */
    protected $cue_in;

    /**
     * @ORM\Column(name="cue_out", type="decimal", precision=5, scale=1, nullable=true)
     *
     * @OA\Property(example=30.00)
     *
     * @var float|null The length of time (in seconds) from the CUE-IN of the track to stop playing;
     *                 equivalent to Liquidsoap's "liq_cue_out" annotation.
     */
    protected $cue_out;

    /**
     * @ORM\OneToMany(targetEntity="StationPlaylistMedia", mappedBy="media")
     *
     * @DeepNormalize(true)
     * @Serializer\MaxDepth(1)
     *
     * @OA\Property(@OA\Items())
     *
     * @var Collection
     */
    protected $playlists;

    /**
     * @ORM\OneToMany(targetEntity="StationMediaCustomField", mappedBy="media")
     *
     * @var Collection
     */
    protected $custom_fields;

    public function __construct(Station $station, string $path)
    {
        $this->station = $station;

        $this->length = 0;
        $this->length_text = '0:00';

        $this->mtime = 0;

        $this->playlists = new ArrayCollection;
        $this->custom_fields = new ArrayCollection;

        $this->setPath($path);
        $this->generateUniqueId();
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
     * @return string|null
     */
    public function getSongId(): ?string
    {
        return $this->song_id;
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
    public function setTitle(string $title = null): void
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
    public function setArtist(string $artist = null): void
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
    public function setAlbum(string $album = null): void
    {
        $this->album = $this->_truncateString($album, 200);
    }

    /**
     * @return null|string
     */
    public function getLyrics(): ?string
    {
        return $this->lyrics;
    }

    /**
     * @param null|string $lyrics
     */
    public function setLyrics($lyrics): void
    {
        $this->lyrics = $lyrics;
    }

    /**
     * Get the Flysystem URI for album artwork for this item.
     *
     * @return string
     */
    public function getArtPath(): string
    {
        return 'albumart://' . $this->unique_id . '.jpg';
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
    public function setIsrc(string $isrc = null): void
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
     * @param int $length
     */
    public function setLength($length): void
    {
        $length_min = floor($length / 60);
        $length_sec = $length % 60;

        $this->length = (int)round($length);
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
    public function setLengthText(string $length_text = null): void
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
    public function setPath(string $path = null): void
    {
        $this->path = $path;
    }

    /**
     * Return the abstracted "full path" filesystem URI for this record.
     *
     * @return string
     */
    public function getPathUri(): string
    {
        return 'media://' . $this->path;
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
    public function setMtime(int $mtime = null): void
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
    public function setFadeOverlap($fade_overlap = null): void
    {
        if ($fade_overlap === '') {
            $fade_overlap = null;
        }

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
    public function setFadeIn($fade_in = null): void
    {
        if ($fade_in === '') {
            $fade_in = null;
        }

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
    public function setFadeOut($fade_out = null): void
    {
        if ($fade_out === '') {
            $fade_out = null;
        }

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
    public function setCueIn($cue_in = null): void
    {
        if ($cue_in === '') {
            $cue_in = null;
        }

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
    public function setCueOut($cue_out = null): void
    {
        if ($cue_out === '') {
            $cue_out = null;
        }

        $this->cue_out = $cue_out;
    }

    /**
     * Get the length with cue-in and cue-out points included.
     *
     * @return int
     */
    public function getCalculatedLength(): int
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
     * @param StationPlaylist $playlist
     * @return StationPlaylistMedia|null
     */
    public function getItemForPlaylist(StationPlaylist $playlist): ?StationPlaylistMedia
    {
        $item = $this->playlists->filter(function ($spm) use ($playlist) {
            /** @var StationPlaylistMedia $spm */
            return $spm->getPlaylist()->getId() === $playlist->getId();
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
     * Indicate whether this media needs reprocessing given certain factors.
     *
     * @param int $current_mtime
     * @return bool
     */
    public function needsReprocessing($current_mtime = 0): bool
    {
        if ($current_mtime > $this->mtime) {
            return true;
        }
        if (!$this->songMatches()) {
            return true;
        }
        return false;
    }

    /**
     * Check if the hash of the associated Song record matches the hash that would be
     *   generated by this record's artist and title metadata. Used to determine if a
     *   record should be reprocessed or not.
     *
     * @return bool
     */
    public function songMatches(): bool
    {
        return (null !== $this->song_id)
            && ($this->song_id === $this->getExpectedSongHash());
    }

    /**
     * Get the appropriate song hash for the title and artist specified here.
     *
     * @return string
     */
    protected function getExpectedSongHash(): string
    {
        return Song::getSongHash([
            'artist' => $this->artist,
            'title' => $this->title,
        ]);
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
            'title' => $this->title,
            'artist' => $this->artist,
            'duration' => $this->length,
            'song_id' => $this->getSong()->getId(),
            'media_id' => $this->id,
            'liq_start_next' => $this->fade_overlap,
            'liq_fade_in' => $this->fade_in,
            'liq_fade_out' => $this->fade_out,
            'liq_cue_in' => $this->cue_in,
            'liq_cue_out' => $this->cue_out,
        ];

        // Safety checks for cue lengths.
        if ($annotation_types['liq_cue_out'] < 0) {
            $cue_out = abs($annotation_types['liq_cue_out']);
            if (0 === $cue_out || $cue_out > $annotation_types['duration']) {
                $annotation_types['liq_cue_out'] = null;
            } else {
                $annotation_types['liq_cue_out'] = max(0, $annotation_types['duration'] - $cue_out);
            }
        }
        if (($annotation_types['liq_cue_in'] + $annotation_types['liq_cue_out']) > $annotation_types['duration']) {
            $annotation_types['liq_cue_out'] = null;
        }
        if ($annotation_types['liq_cue_in'] > $annotation_types['duration']) {
            $annotation_types['liq_cue_in'] = null;
        }

        foreach ($annotation_types as $annotation_name => $prop) {
            if (null === $prop) {
                continue;
            }

            $prop = mb_convert_encoding($prop, 'UTF-8');
            $prop = str_replace(['"', "\n", "\t", "\r"], ["'", '', '', ''], $prop);

            // Convert Liquidsoap-specific annotations to floats.
            if ('duration' === $annotation_name || 0 === strpos($annotation_name, 'liq')) {
                $prop = Liquidsoap::toFloat($prop);
            }

            $annotations[$annotation_name] = $prop;
        }

        return $annotations;
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
    public function setSong(Song $song = null): void
    {
        $this->song = $song;
    }

    /**
     * Indicates whether this media is a part of any "requestable" playlists.
     *
     * @return bool
     */
    public function isRequestable(): bool
    {
        $playlists = $this->getPlaylists();
        foreach ($playlists as $playlist_item) {
            $playlist = $playlist_item->getPlaylist();
            /** @var StationPlaylist $playlist */
            if ($playlist->isRequestable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    /**
     * @return string A string identifying this entity.
     */
    public function __toString(): string
    {
        return $this->unique_id.': '.$this->artist.' - '.$this->title;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param ApiUtilities $api_utils
     * @param UriInterface|null $base_url
     * @return Api\Song
     */
    public function api(ApiUtilities $api_utils, UriInterface $base_url = null): Api\Song
    {
        $response = new Api\Song;
        $response->id = (string)$this->song_id;
        $response->text = $this->artist . ' - ' . $this->title;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;

        $response->album = (string)$this->album;
        $response->lyrics = (string)$this->lyrics;

        $response->art = $api_utils->getAlbumArtUrl($this->station_id, $this->unique_id, $base_url);
        $response->custom_fields = $api_utils->getCustomFields($this->id);

        return $response;
    }
}
