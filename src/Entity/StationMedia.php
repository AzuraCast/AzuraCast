<?php
namespace App\Entity;

use App\Annotations\AuditLog;
use App\ApiUtilities;
use App\Flysystem\Filesystem;
use App\Normalizer\Annotation\DeepNormalize;
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
 * @ORM\Entity()
 *
 * @OA\Schema(type="object")
 */
class StationMedia
{
    use Traits\UniqueId, Traits\TruncateStrings;

    public const UNIQUE_ID_LENGTH = 24;

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
     * @ORM\Column(name="length", type="decimal", precision=7, scale=2)
     *
     * @OA\Property(example=240.00)
     *
     * @var float The song duration in seconds.
     */
    protected $length = 0.00;

    /**
     * @ORM\Column(name="length_text", type="string", length=10, nullable=true)
     *
     * @OA\Property(example="4:00")
     *
     * @var string|null The formatted song duration (in mm:ss format)
     */
    protected $length_text = '0:00';

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
    protected $mtime = 0;

    /**
     * @ORM\Column(name="amplify", type="decimal", precision=3, scale=1, nullable=true)
     *
     * @OA\Property(example=-14.00)
     *
     * @var float|null The amount of amplification (in dB) to be applied to the radio source;
     *                 equivalent to Liquidsoap's "liq_amplify" annotation.
     */
    protected $amplify;

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
     * @ORM\Column(name="art_updated_at", type="integer")
     * @AuditLog\AuditIgnore()
     *
     * @OA\Property(example=SAMPLE_TIMESTAMP)
     * @var int The latest time (UNIX timestamp) when album art was updated.
     */
    protected $art_updated_at = 0;

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

        $this->playlists = new ArrayCollection;
        $this->custom_fields = new ArrayCollection;

        $this->setPath($path);
        $this->generateUniqueId();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStation(): Station
    {
        return $this->station;
    }

    public function getSongId(): ?string
    {
        return $this->song_id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title = null): void
    {
        $this->title = $this->truncateString($title, 200);
    }

    public function getArtist(): ?string
    {
        return $this->artist;
    }

    public function setArtist(?string $artist = null): void
    {
        $this->artist = $this->truncateString($artist, 200);
    }

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(?string $album = null): void
    {
        $this->album = $this->truncateString($album, 200);
    }

    public function getLyrics(): ?string
    {
        return $this->lyrics;
    }

    public function setLyrics(?string $lyrics = null): void
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
        return Filesystem::PREFIX_ALBUM_ART . '://' . $this->unique_id . '.jpg';
    }

    public function getWaveformPath(): string
    {
        return Filesystem::PREFIX_WAVEFORMS . '://' . $this->unique_id . '.json';
    }

    public function getIsrc(): ?string
    {
        return $this->isrc;
    }

    public function setIsrc(?string $isrc = null): void
    {
        $this->isrc = $isrc;
    }

    public function getLength(): float
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

        $this->length = (float)$length;
        $this->length_text = $length_min . ':' . str_pad($length_sec, 2, '0', STR_PAD_LEFT);
    }

    public function getLengthText(): ?string
    {
        return $this->length_text;
    }

    public function setLengthText(?string $length_text = null): void
    {
        $this->length_text = $length_text;
    }

    public function getPath(): ?string
    {
        return $this->path;
    }

    public function setPath(?string $path = null): void
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
        return Filesystem::PREFIX_MEDIA . '://' . $this->path;
    }

    public function getMtime(): ?int
    {
        return $this->mtime;
    }

    public function setMtime(?int $mtime = null): void
    {
        $this->mtime = $mtime;
    }

    public function getAmplify(): ?float
    {
        return $this->amplify;
    }

    public function setAmplify(?float $amplify = null): void
    {
        if ($amplify === '') {
            $amplify = null;
        }

        $this->amplify = (null === $amplify) ? null : (float)$amplify;
    }

    public function getFadeOverlap(): ?float
    {
        return $this->fade_overlap;
    }

    public function setFadeOverlap(?float $fade_overlap = null): void
    {
        if ($fade_overlap === '') {
            $fade_overlap = null;
        }

        $this->fade_overlap = $fade_overlap;
    }

    public function getFadeIn(): ?float
    {
        return $this->fade_in;
    }

    /**
     * @param string|float|null $fade_in
     */
    public function setFadeIn($fade_in = null): void
    {
        $this->fade_in = $this->parseSeconds($fade_in);
    }

    public function getFadeOut(): ?float
    {
        return $this->fade_out;
    }

    /**
     * @param string|float|null $fade_out
     */
    public function setFadeOut($fade_out = null): void
    {
        $this->fade_out = $this->parseSeconds($fade_out);
    }

    public function getCueIn(): ?float
    {
        return $this->cue_in;
    }

    /**
     * @param string|float|null $cue_in
     */
    public function setCueIn($cue_in = null): void
    {
        $this->cue_in = $this->parseSeconds($cue_in);
    }

    public function getCueOut(): ?float
    {
        return $this->cue_out;
    }

    /**
     * @param string|float|null $cue_out
     */
    public function setCueOut($cue_out = null): void
    {
        $this->cue_out = $this->parseSeconds($cue_out);
    }

    /**
     * @param string|float|null $seconds
     *
     * @return float|null
     */
    protected function parseSeconds($seconds = null): ?float
    {
        if ($seconds === '') {
            return null;
        }

        if (false !== strpos($seconds, ':')) {
            $sec = 0;
            foreach (array_reverse(explode(':', $seconds)) as $k => $v) {
                $sec += (60 ** (int)$k) * (int)$v;
            }

            return $sec;
        }

        return $seconds;
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

    public function getArtUpdatedAt(): int
    {
        return $this->art_updated_at;
    }

    public function setArtUpdatedAt(int $art_updated_at): void
    {
        $this->art_updated_at = $art_updated_at;
    }

    public function getItemForPlaylist(StationPlaylist $playlist): ?StationPlaylistMedia
    {
        $item = $this->playlists->filter(function ($spm) use ($playlist) {
            /** @var StationPlaylistMedia $spm */
            return $spm->getPlaylist()->getId() === $playlist->getId();
        });

        $firstItem = $item->first();

        return ($firstItem instanceof StationPlaylistMedia)
            ? $firstItem
            : null;
    }

    public function getCustomFields(): Collection
    {
        return $this->custom_fields;
    }

    public function setCustomFields(Collection $custom_fields): void
    {
        $this->custom_fields = $custom_fields;
    }

    /**
     * Indicate whether this media needs reprocessing given certain factors.
     *
     * @param int $current_mtime
     *
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

    public function getSong(): ?Song
    {
        return $this->song;
    }

    public function setSong(?Song $song = null): void
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

    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function __toString(): string
    {
        return 'StationMedia ' . $this->unique_id . ': ' . $this->artist . ' - ' . $this->title;
    }

    /**
     * Retrieve the API version of the object/array.
     *
     * @param ApiUtilities $apiUtils
     * @param UriInterface|null $baseUri
     *
     * @return Api\Song
     */
    public function api(ApiUtilities $apiUtils, UriInterface $baseUri = null): Api\Song
    {
        $response = new Api\Song;
        $response->id = (string)$this->song_id;
        $response->text = $this->artist . ' - ' . $this->title;
        $response->artist = (string)$this->artist;
        $response->title = (string)$this->title;

        $response->album = (string)$this->album;
        $response->lyrics = (string)$this->lyrics;

        $response->art = $apiUtils->getAlbumArtUrl(
            $this->station,
            $this->unique_id,
            $this->art_updated_at,
            $baseUri
        );
        $response->custom_fields = $apiUtils->getCustomFields($this->id);

        return $response;
    }
}
