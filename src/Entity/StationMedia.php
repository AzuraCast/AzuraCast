<?php

namespace App\Entity;

use App\Annotations\AuditLog;
use App\Flysystem\FilesystemManager;
use App\Media\Metadata;
use App\Normalizer\Annotation\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation as Serializer;

/**
 * @ORM\Table(name="station_media", indexes={
 *   @ORM\Index(name="search_idx", columns={"title", "artist", "album"})
 * }, uniqueConstraints={
 *   @ORM\UniqueConstraint(name="path_unique_idx", columns={"path", "storage_location_id"})
 * })
 * @ORM\Entity()
 *
 * @OA\Schema(type="object")
 */
class StationMedia implements SongInterface
{
    use Traits\UniqueId;
    use Traits\TruncateStrings;
    use Traits\HasSongFields;

    public const UNIQUE_ID_LENGTH = 24;

    public const DIR_ALBUM_ART = '.albumart';
    public const DIR_WAVEFORMS = '.waveforms';

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
     * @ORM\Column(name="storage_location_id", type="integer")
     * @var int
     */
    protected $storage_location_id;

    /**
     * @ORM\ManyToOne(targetEntity="StorageLocation", inversedBy="media")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="storage_location_id", referencedColumnName="id", onDelete="CASCADE")
     * })
     * @var StorageLocation
     */
    protected $storage_location;

    /**
     * @ORM\Column(name="album", type="string", length=200, nullable=true)
     *
     * @OA\Property(example="Test Album")
     *
     * @var string|null The name of the media file's album.
     */
    protected $album;

    /**
     * @ORM\Column(name="genre", type="string", length=30, nullable=true)
     *
     * @OA\Property(example="Rock")
     *
     * @var string|null The genre of the media file.
     */
    protected $genre;

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
     * @ORM\Column(name="length", type="decimal", precision=7, scale=2, nullable=true)
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

    public function __construct(StorageLocation $storageLocation, string $path)
    {
        $this->storage_location = $storageLocation;

        $this->playlists = new ArrayCollection();
        $this->custom_fields = new ArrayCollection();

        $this->setPath($path);
        $this->generateUniqueId();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getStorageLocation(): StorageLocation
    {
        return $this->storage_location;
    }

    public function getAlbum(): ?string
    {
        return $this->album;
    }

    public function setAlbum(?string $album = null): void
    {
        $this->album = $this->truncateString($album, 200);
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre = null): void
    {
        $this->genre = $this->truncateString($genre, 30);
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
     * @return string[]
     */
    public function getRelatedFilePaths(): array
    {
        return [
            self::getArtPath($this->getUniqueId()),
            self::getWaveformPath($this->getUniqueId()),
        ];
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
        $this->length_text = $length_min . ':' . str_pad((string)$length_sec, 2, '0', STR_PAD_LEFT);
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
     */
    public function getPathUri(): string
    {
        return FilesystemManager::PREFIX_MEDIA . '://' . $this->path;
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
        $this->amplify = $amplify;
    }

    public function getFadeOverlap(): ?float
    {
        return $this->fade_overlap;
    }

    public function setFadeOverlap(?float $fade_overlap = null): void
    {
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

    public function needsReprocessing($current_mtime = 0): bool
    {
        return $current_mtime > $this->mtime;
    }

    /**
     * Indicates whether this media is a part of any "requestable" playlists.
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
     * @return StationPlaylistMedia[]|Collection
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function fromMetadata(Metadata $metadata): void
    {
        $this->setLength($metadata->getDuration());

        $tags = $metadata->getTags();

        if (isset($tags['title'])) {
            $this->setTitle($tags['title']);
        }
        if (isset($tags['artist'])) {
            $this->setArtist($tags['artist']);
        }
        if (isset($tags['album'])) {
            $this->setAlbum($tags['album']);
        }
        if (isset($tags['genre'])) {
            $this->setGenre($tags['genre']);
        }
        if (isset($tags['unsynchronised_lyric'])) {
            $this->setLyrics($tags['unsynchronised_lyric']);
        }
        if (isset($tags['isrc'])) {
            $this->setIsrc($tags['isrc']);
        }
    }

    public function toMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->setDuration($this->getLength());

        $tagsToSet = array_filter([
            'title' => $this->getTitle(),
            'artist' => $this->getArtist(),
            'album' => $this->getAlbum(),
            'genre' => $this->getGenre(),
            'unsynchronised_lyric' => $this->getLyrics(),
            'isrc' => $this->getIsrc(),
        ]);

        $tags = $metadata->getTags();
        foreach ($tagsToSet as $tagKey => $tagValue) {
            $tags->set($tagKey, $tagValue);
        }

        return $metadata;
    }

    public function __toString(): string
    {
        return 'StationMedia ' . $this->unique_id . ': ' . $this->artist . ' - ' . $this->title;
    }

    public static function getArtPath(string $uniqueId): string
    {
        return self::DIR_ALBUM_ART . '/' . $uniqueId . '.jpg';
    }

    public static function getArtUri(string $uniqueId): string
    {
        return FilesystemManager::PREFIX_MEDIA . '://' . ltrim(self::getArtPath($uniqueId), '/');
    }

    public static function getWaveformPath(string $uniqueId): string
    {
        return self::DIR_WAVEFORMS . '/' . $uniqueId . '.json';
    }

    public static function getWaveformUri(string $uniqueId): string
    {
        return FilesystemManager::PREFIX_MEDIA . '://' . ltrim(self::getWaveformPath($uniqueId), '/');
    }
}
