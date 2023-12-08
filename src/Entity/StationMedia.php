<?php

declare(strict_types=1);

namespace App\Entity;

use App\Flysystem\StationFilesystems;
use App\Media\Metadata;
use App\Media\MetadataInterface;
use App\Normalizer\Attributes\DeepNormalize;
use App\OpenApi;
use App\Utilities\Time;
use App\Utilities\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Exception;
use OpenApi\Attributes as OA;
use RuntimeException;
use Symfony\Component\Serializer\Annotation as Serializer;

#[
    OA\Schema(type: "object"),
    ORM\Entity,
    ORM\Table(name: 'station_media'),
    ORM\Index(columns: ['title', 'artist', 'album'], name: 'search_idx'),
    ORM\UniqueConstraint(name: 'path_unique_idx', columns: ['path', 'storage_location_id'])
]
class StationMedia implements
    Interfaces\SongInterface,
    Interfaces\ProcessableMediaInterface,
    Interfaces\PathAwareInterface,
    Interfaces\IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\HasSongFields;

    public const UNIQUE_ID_LENGTH = 24;

    #[
        OA\Property(
            description: "A unique identifier associated with this record.",
            example: "69b536afc7ebbf16457b8645"
        ),
        ORM\Column(length: 25, nullable: true)
    ]
    protected ?string $unique_id = null;

    #[
        ORM\ManyToOne(inversedBy: 'media'),
        ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected StorageLocation $storage_location;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $storage_location_id;

    #[
        OA\Property(
            description: "The name of the media file's album.",
            example: "Test Album"
        ),
        ORM\Column(length: 200, nullable: true)
    ]
    protected ?string $album = null;

    #[
        OA\Property(
            description: "The genre of the media file.",
            example: "Rock"
        ),
        ORM\Column(length: 255, nullable: true)
    ]
    protected ?string $genre = null;

    #[
        OA\Property(
            description: "Full lyrics of the track, if available.",
            example: "...Never gonna give you up..."
        ),
        ORM\Column(type: 'text', nullable: true)
    ]
    protected ?string $lyrics = null;

    #[
        OA\Property(
            description: "The track ISRC (International Standard Recording Code), used for licensing purposes.",
            example: "GBARL0600786"
        ),
        ORM\Column(length: 15, nullable: true)
    ]
    protected ?string $isrc = null;

    #[
        OA\Property(
            description: "The song duration in seconds.",
            example: 240.00
        ),
        ORM\Column(type: 'decimal', precision: 7, scale: 2, nullable: true)
    ]
    protected ?string $length = '0.00';

    #[
        OA\Property(
            description: "The formatted song duration (in mm:ss format)",
            example: "4:00"
        ),
        ORM\Column(length: 10, nullable: true)
    ]
    protected ?string $length_text = '0:00';

    #[
        OA\Property(
            description: "The relative path of the media file.",
            example: "test.mp3"
        ),
        ORM\Column(length: 500)
    ]
    protected string $path;

    #[
        OA\Property(
            description: "The UNIX timestamp when the database was last modified.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column(nullable: true)
    ]
    protected ?int $mtime = 0;

    #[
        OA\Property(
            description: "The amount of amplification (in dB) to be applied to the radio source (liq_amplify)",
            example: -14.00
        ),
        ORM\Column(type: 'decimal', precision: 6, scale: 1, nullable: true)
    ]
    protected ?string $amplify = null;

    #[
        OA\Property(
            description: "The length of time (in seconds) before the next song starts in the fade (liq_start_next)",
            example: 2.00
        ),
        ORM\Column(type: 'decimal', precision: 6, scale: 1, nullable: true)
    ]
    protected ?string $fade_overlap = null;

    #[
        OA\Property(
            description: "The length of time (in seconds) to fade in the next track (liq_fade_in)",
            example: 3.00
        ),
        ORM\Column(type: 'decimal', precision: 6, scale: 1, nullable: true)
    ]
    protected ?string $fade_in = null;

    #[
        OA\Property(
            description: "The length of time (in seconds) to fade out the previous track (liq_fade_out)",
            example: 3.00
        ),
        ORM\Column(type: 'decimal', precision: 6, scale: 1, nullable: true)
    ]
    protected ?string $fade_out = null;

    #[
        OA\Property(
            description: "The length of time (in seconds) from the start of the track to start playing (liq_cue_in)",
            example: 30.00
        ),
        ORM\Column(type: 'decimal', precision: 6, scale: 1, nullable: true)
    ]
    protected ?string $cue_in = null;

    #[
        OA\Property(
            description: "The length of time (in seconds) from the CUE-IN of the track to stop playing (liq_cue_out)",
            example: 30.00
        ),
        ORM\Column(type: 'decimal', precision: 6, scale: 1, nullable: true)
    ]
    protected ?string $cue_out = null;

    #[
        OA\Property(
            description: "The latest time (UNIX timestamp) when album art was updated.",
            example: OpenApi::SAMPLE_TIMESTAMP
        ),
        ORM\Column
    ]
    protected int $art_updated_at = 0;

    /** @var Collection<int, StationPlaylistMedia> */
    #[
        OA\Property(type: "array", items: new OA\Items()),
        ORM\OneToMany(mappedBy: 'media', targetEntity: StationPlaylistMedia::class),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    protected Collection $playlists;

    /** @var Collection<int, StationMediaCustomField> */
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
            throw new RuntimeException('Unique ID has not been generated yet.');
        }

        return $this->unique_id;
    }

    /**
     * Generate a new unique ID for this item.
     *
     * @param bool $forceNew
     *
     * @throws Exception
     */
    public function generateUniqueId(bool $forceNew = false): void
    {
        if (!isset($this->unique_id) || $forceNew) {
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
        $this->genre = $this->truncateNullableString($genre);
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

    public function getLength(): ?float
    {
        return Types::floatOrNull($this->length);
    }

    public function setLength(float $length): void
    {
        $lengthMin = floor($length / 60);
        $lengthSec = $length % 60;

        $this->length = (string)$length;
        $this->length_text = $lengthMin . ':' . str_pad((string)$lengthSec, 2, '0', STR_PAD_LEFT);
    }

    public function getLengthText(): ?string
    {
        return $this->length_text;
    }

    public function setLengthText(?string $lengthText = null): void
    {
        $this->length_text = $lengthText;
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
        return Types::floatOrNull($this->amplify);
    }

    public function setAmplify(?float $amplify = null): void
    {
        $this->amplify = Types::stringOrNull($amplify, true);
    }

    public function getFadeOverlap(): ?float
    {
        return Types::floatOrNull($this->fade_overlap);
    }

    public function setFadeOverlap(?float $fadeOverlap = null): void
    {
        $this->fade_overlap = Types::stringOrNull($fadeOverlap, true);
    }

    public function getFadeIn(): ?float
    {
        return Types::floatOrNull($this->fade_in);
    }

    public function setFadeIn(string|int|float $fadeIn = null): void
    {
        $this->fade_in = Types::stringOrNull(Time::displayTimeToSeconds($fadeIn), true);
    }

    public function getFadeOut(): ?float
    {
        return Types::floatOrNull($this->fade_out);
    }

    public function setFadeOut(string|int|float $fadeOut = null): void
    {
        $this->fade_out = Types::stringOrNull(Time::displayTimeToSeconds($fadeOut), true);
    }

    public function getCueIn(): ?float
    {
        return Types::floatOrNull($this->cue_in);
    }

    public function setCueIn(string|int|float $cueIn = null): void
    {
        $this->cue_in = Types::stringOrNull(Time::displayTimeToSeconds($cueIn), true);
    }

    public function getCueOut(): ?float
    {
        return Types::floatOrNull($this->cue_out);
    }

    public function setCueOut(string|int|float $cueOut = null): void
    {
        $this->cue_out = Types::stringOrNull(Time::displayTimeToSeconds($cueOut), true);
    }

    /**
     * Get the length with cue-in and cue-out points included.
     */
    public function getCalculatedLength(): int
    {
        $length = $this->getLength() ?? 0.0;

        $cueOut = $this->getCueOut();
        if ($cueOut > 0) {
            $lengthRemoved = $length - $cueOut;
            $length -= $lengthRemoved;
        }

        $cueIn = $this->getCueIn();
        if ($cueIn > 0) {
            $length -= $cueIn;
        }

        return (int)floor($length);
    }

    public function getArtUpdatedAt(): int
    {
        return $this->art_updated_at;
    }

    public function setArtUpdatedAt(int $artUpdatedAt): void
    {
        $this->art_updated_at = $artUpdatedAt;
    }

    /**
     * @return Collection<int, StationMediaCustomField>
     */
    public function getCustomFields(): Collection
    {
        return $this->custom_fields;
    }

    /**
     * @param Collection<int, StationMediaCustomField> $customFields
     */
    public function setCustomFields(Collection $customFields): void
    {
        $this->custom_fields = $customFields;
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
        foreach ($this->getPlaylists() as $playlistItem) {
            $playlist = $playlistItem->getPlaylist();
            /** @var StationPlaylist $playlist */
            if ($playlist->isRequestable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return Collection<int, StationPlaylistMedia>
     */
    public function getPlaylists(): Collection
    {
        return $this->playlists;
    }

    public function fromMetadata(MetadataInterface $metadata): void
    {
        $this->setLength($metadata->getDuration());

        $tags = $metadata->getTags();

        if (isset($tags['title'])) {
            $this->setTitle(Types::stringOrNull($tags['title']));
        }
        if (isset($tags['artist'])) {
            $this->setArtist(Types::stringOrNull($tags['artist']));
        }
        if (isset($tags['album'])) {
            $this->setAlbum(Types::stringOrNull($tags['album']));
        }
        if (isset($tags['genre'])) {
            $this->setGenre(Types::stringOrNull($tags['genre']));
        }
        if (isset($tags['unsynchronised_lyric'])) {
            $this->setLyrics(Types::stringOrNull($tags['unsynchronised_lyric']));
        }
        if (isset($tags['isrc'])) {
            $this->setIsrc(Types::stringOrNull($tags['isrc']));
        }

        $this->updateSongId();
    }

    public function toMetadata(): MetadataInterface
    {
        $metadata = new Metadata();
        $metadata->setDuration($this->getLength() ?? 0.0);

        $metadata->setTags(
            array_filter(
                [
                    'title' => $this->getTitle(),
                    'artist' => $this->getArtist(),
                    'album' => $this->getAlbum(),
                    'genre' => $this->getGenre(),
                    'unsynchronised_lyric' => $this->getLyrics(),
                    'isrc' => $this->getIsrc(),
                ]
            )
        );

        return $metadata;
    }

    public function __toString(): string
    {
        return 'StationMedia ' . $this->unique_id . ': ' . $this->artist . ' - ' . $this->title;
    }

    public static function getArtPath(string $uniqueId): string
    {
        return StationFilesystems::DIR_ALBUM_ART . '/' . $uniqueId . '.jpg';
    }

    public static function getFolderArtPath(string $folderHash): string
    {
        return StationFilesystems::DIR_FOLDER_COVERS . '/' . $folderHash . '.jpg';
    }

    public static function getFolderHashForPath(string $path): string
    {
        $folder = dirname($path);
        return (!empty($folder))
            ? md5($folder)
            : 'base';
    }

    public static function getWaveformPath(string $uniqueId): string
    {
        return StationFilesystems::DIR_WAVEFORMS . '/' . $uniqueId . '.json';
    }
}
