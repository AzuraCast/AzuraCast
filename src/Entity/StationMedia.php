<?php

/** @noinspection PhpMissingFieldTypeInspection */

namespace App\Entity;

use App\Entity\Interfaces\PathAwareInterface;
use App\Entity\Interfaces\ProcessableMediaInterface;
use App\Entity\Interfaces\SongInterface;
use App\Normalizer\Annotation\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation as Serializer;

/** @OA\Schema(type="object") */
#[
    ORM\Entity,
    ORM\Table(name: 'station_media'),
    ORM\Index(columns: ['title', 'artist', 'album'], name: 'search_idx'),
    ORM\UniqueConstraint(name: 'path_unique_idx', columns: ['path', 'storage_location_id'])
]
class StationMedia implements SongInterface, ProcessableMediaInterface, PathAwareInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\TruncateStrings;
    use Traits\HasSongFields;

    public const UNIQUE_ID_LENGTH = 24;

    public const DIR_ALBUM_ART = '.albumart';
    public const DIR_WAVEFORMS = '.waveforms';

    /**
     * @OA\Property(
     *     description="A unique identifier associated with this record.",
     *     example="69b536afc7ebbf16457b8645"
     * )
     */
    #[ORM\Column(length: 25)]
    protected ?string $unique_id = null;

    #[ORM\Column]
    protected int $storage_location_id;

    #[ORM\ManyToOne(inversedBy: 'media')]
    #[ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected StorageLocation $storage_location;

    /**
     * @OA\Property(
     *     description="The name of the media file's album.",
     *     example="Test Album"
     * )
     */
    #[ORM\Column(length: 200)]
    protected ?string $album = null;

    /**
     * @OA\Property(
     *     description="The genre of the media file.",
     *     example="Rock"
     * )
     */
    #[ORM\Column(length: 30)]
    protected ?string $genre = null;

    /**
     * @OA\Property(
     *     description="Full lyrics of the track, if available.",
     *     example="...Never gonna give you up..."
     * )
     */
    #[ORM\Column(type: 'text')]
    protected ?string $lyrics = null;

    /**
     * @OA\Property(
     *     description="The track ISRC (International Standard Recording Code), used for licensing purposes.",
     *     example="GBARL0600786"
     * )
     */
    #[ORM\Column(length: 15)]
    protected ?string $isrc = null;

    /**
     * @OA\Property(
     *     description="The song duration in seconds.",
     *     example=240.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 7, scale: 2)]
    protected ?float $length = 0.00;

    /**
     * @OA\Property(
     *     description="The formatted song duration (in mm:ss format)",
     *     example="4:00"
     * )
     */
    #[ORM\Column(length: 10)]
    protected ?string $length_text = '0:00';

    /**
     * @OA\Property(
     *     description="The relative path of the media file.",
     *     example="test.mp3"
     * )
     */
    #[ORM\Column(length: 500)]
    protected string $path;

    /**
     * @OA\Property(
     *     description="The UNIX timestamp when the database was last modified.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    protected ?int $mtime = 0;

    /**
     * @OA\Property(
     *     description="The amount of amplification (in dB) to be applied to the radio source (liq_amplify)",
     *     example=-14.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 1)]
    protected ?float $amplify = null;

    /**
     * @OA\Property(
     *     description="The length of time (in seconds) before the next song starts in the fade (liq_start_next)",
     *     example=2.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 1)]
    protected ?float $fade_overlap = null;

    /**
     * @OA\Property(
     *     description="The length of time (in seconds) to fade in the next track (liq_fade_in)",
     *     example=3.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 1)]
    protected ?float $fade_in = null;

    /**
     * @OA\Property(
     *     description="The length of time (in seconds) to fade out the previous track (liq_fade_out)",
     *     example=3.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 1)]
    protected ?float $fade_out = null;

    /**
     * @OA\Property(
     *     description="The length of time (in seconds) from the start of the track to start playing (liq_cue_in)",
     *     example=30.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 1)]
    protected ?float $cue_in = null;

    /**
     * @OA\Property(
     *     description="The length of time (in seconds) from the CUE-IN of the track to stop playing (liq_cue_out)",
     *     example=30.00
     * )
     */
    #[ORM\Column(type: 'decimal', precision: 6, scale: 1)]
    protected ?float $cue_out = null;

    /**
     * @OA\Property(
     *     description="The latest time (UNIX timestamp) when album art was updated.",
     *     example=SAMPLE_TIMESTAMP
     * )
     */
    #[ORM\Column]
    protected int $art_updated_at = 0;

    /** @OA\Property(type="array", @OA\Items()) */
    #[ORM\OneToMany(mappedBy: 'media', targetEntity: StationPlaylistMedia::class)]
    #[DeepNormalize(true)]
    #[Serializer\MaxDepth(1)]
    protected Collection $playlists;

    #[ORM\OneToMany(mappedBy: 'media', targetEntity: StationMediaCustomField::class)]
    protected Collection $custom_fields;

    public function __construct(StorageLocation $storageLocation, string $path)
    {
        $this->storage_location = $storageLocation;

        $this->playlists = new ArrayCollection();
        $this->custom_fields = new ArrayCollection();

        $this->setPath($path);
        $this->generateUniqueId();
    }

    public function getUniqueId(): string
    {
        if (!isset($this->unique_id)) {
            throw new \RuntimeException('Unique ID has not been generated yet.');
        }

        return $this->unique_id;
    }

    /**
     * Generate a new unique ID for this item.
     *
     * @param bool $force_new
     *
     * @throws Exception
     */
    public function generateUniqueId($force_new = false): void
    {
        if (!isset($this->unique_id) || $force_new) {
            $this->unique_id = bin2hex(random_bytes(12));
        }
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
        $this->album = $this->truncateNullableString($album, 200);
    }

    public function getGenre(): ?string
    {
        return $this->genre;
    }

    public function setGenre(?string $genre = null): void
    {
        $this->genre = $this->truncateNullableString($genre, 30);
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
        $this->isrc = $this->truncateNullableString($isrc, 15);
    }

    public function getLength(): float
    {
        return $this->length;
    }

    /**
     * @param int $length
     */
    public function setLength(int $length): void
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

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
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

        if (str_contains($seconds, ':')) {
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

    public function getCustomFields(): Collection
    {
        return $this->custom_fields;
    }

    public function setCustomFields(Collection $custom_fields): void
    {
        $this->custom_fields = $custom_fields;
    }

    public static function needsReprocessing(int $fileModifiedTime = 0, int $dbModifiedTime = 0): bool
    {
        return $fileModifiedTime > $dbModifiedTime;
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

        $this->updateSongId();
    }

    public function toMetadata(): Metadata
    {
        $metadata = new Metadata();
        $metadata->setDuration($this->getLength());

        $tagsToSet = array_filter(
            [
                'title' => $this->getTitle(),
                'artist' => $this->getArtist(),
                'album' => $this->getAlbum(),
                'genre' => $this->getGenre(),
                'unsynchronised_lyric' => $this->getLyrics(),
                'isrc' => $this->getIsrc(),
            ]
        );

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

    public static function getWaveformPath(string $uniqueId): string
    {
        return self::DIR_WAVEFORMS . '/' . $uniqueId . '.json';
    }
}
