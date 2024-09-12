<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Interfaces\PathAwareInterface;
use App\Entity\Interfaces\ProcessableMediaInterface;
use App\Entity\Interfaces\SongInterface;
use App\Flysystem\StationFilesystems;
use App\Media\Metadata;
use App\Media\MetadataInterface;
use App\Utilities\Types;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation as Serializer;

#[
    ORM\Entity,
    ORM\Table(name: 'station_media'),
    ORM\Index(name: 'search_idx', columns: ['title', 'artist', 'album']),
    ORM\UniqueConstraint(name: 'path_unique_idx', columns: ['path', 'storage_location_id'])
]
class StationMedia implements
    SongInterface,
    ProcessableMediaInterface,
    PathAwareInterface,
    IdentifiableEntityInterface
{
    use Traits\HasAutoIncrementId;
    use Traits\HasSongFields;

    public const int UNIQUE_ID_LENGTH = 24;

    #[
        ORM\ManyToOne(inversedBy: 'media'),
        ORM\JoinColumn(name: 'storage_location_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')
    ]
    protected StorageLocation $storage_location;

    #[ORM\Column(nullable: false, insertable: false, updatable: false)]
    protected int $storage_location_id;

    #[ORM\Column(length: 25, nullable: false)]
    protected string $unique_id;

    #[ORM\Column(length: 200, nullable: true)]
    protected ?string $album = null;

    #[ORM\Column(length: 255, nullable: true)]
    protected ?string $genre = null;

    #[ORM\Column(type: 'text', nullable: true)]
    protected ?string $lyrics = null;

    #[ORM\Column(length: 15, nullable: true)]
    protected ?string $isrc = null;

    #[ORM\Column(type: 'decimal', precision: 7, scale: 2, nullable: false)]
    protected string $length = '0.00';

    #[ORM\Column(length: 500)]
    protected string $path;

    #[ORM\Column(nullable: false)]
    protected int $mtime;

    #[ORM\Column(nullable: false)]
    protected int $uploaded_at;

    #[ORM\Column]
    protected int $art_updated_at = 0;

    #[ORM\Column(type: 'json', nullable: true)]
    protected ?array $extra_metadata = null;

    /** @var Collection<int, StationPlaylistMedia> */
    #[
        ORM\OneToMany(targetEntity: StationPlaylistMedia::class, mappedBy: 'media'),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    protected Collection $playlists;

    /** @var Collection<int, StationMediaCustomField> */
    #[ORM\OneToMany(targetEntity: StationMediaCustomField::class, mappedBy: 'media')]
    protected Collection $custom_fields;

    /** @var Collection<int, PodcastEpisode> */
    #[ORM\OneToMany(targetEntity: PodcastEpisode::class, mappedBy: 'playlist_media')]
    protected Collection $podcast_episodes;

    public function __construct(StorageLocation $storageLocation, string $path)
    {
        $this->storage_location = $storageLocation;

        $this->playlists = new ArrayCollection();
        $this->custom_fields = new ArrayCollection();
        $this->podcast_episodes = new ArrayCollection();

        $this->mtime = $this->uploaded_at = time();

        $this->generateUniqueId();

        $this->setPath($path);
    }

    public function getUniqueId(): string
    {
        return $this->unique_id;
    }

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

    public function getLength(): float
    {
        return Types::float($this->length);
    }

    public function setLength(float $length): void
    {
        $this->length = (string)$length;
    }

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = $path;
    }

    public function getMtime(): int
    {
        return $this->mtime;
    }

    public function setMtime(int $mtime): void
    {
        $this->mtime = $mtime;
    }

    public function getUploadedAt(): int
    {
        return $this->uploaded_at;
    }

    public function getArtUpdatedAt(): int
    {
        return $this->art_updated_at;
    }

    public function setArtUpdatedAt(int $artUpdatedAt): void
    {
        $this->art_updated_at = $artUpdatedAt;
    }

    public function getExtraMetadata(): StationMediaMetadata
    {
        return new StationMediaMetadata($this->extra_metadata ?? []);
    }

    public function setExtraMetadata(
        StationMediaMetadata|array $metadata
    ): void {
        $this->extra_metadata = $this->getExtraMetadata()
            ->fromArray($metadata)
            ->toArray();
    }

    public function clearExtraMetadata(): void
    {
        $this->extra_metadata = null;
    }

    /**
     * Get the length with cue-in and cue-out points included.
     */
    public function getCalculatedLength(): int
    {
        $length = $this->getLength();

        $extraMeta = $this->getExtraMetadata();

        $cueOut = $extraMeta->getCueOut();
        if ($cueOut > 0) {
            $lengthRemoved = $length - $cueOut;
            $length -= $lengthRemoved;
        }

        $cueIn = $extraMeta->getCueIn();
        if ($cueIn > 0) {
            $length -= $cueIn;
        }

        return (int)floor($length);
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

    /**
     * @return Collection<int, PodcastEpisode>
     */
    public function getPodcastEpisodes(): Collection
    {
        return $this->podcast_episodes;
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

        $tags = $metadata->getKnownTags();

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

        $this->setExtraMetadata($metadata->getExtraTags());
        $this->updateSongId();
    }

    public function toMetadata(): MetadataInterface
    {
        $metadata = new Metadata();
        $metadata->setDuration($this->getLength());

        $tags = array_filter(
            [
                'title' => $this->getTitle(),
                'artist' => $this->getArtist(),
                'album' => $this->getAlbum(),
                'genre' => $this->getGenre(),
                'unsynchronised_lyric' => $this->getLyrics(),
                'isrc' => $this->getIsrc(),
            ]
        );

        $metadata->setKnownTags($tags);
        $metadata->setExtraTags($this->getExtraMetadata()->toArray() ?? []);

        return $metadata;
    }

    public function __toString(): string
    {
        return 'StationMedia ' . $this->id . ': ' . $this->artist . ' - ' . $this->title;
    }

    public static function needsReprocessing(int $fileModifiedTime = 0, int $dbModifiedTime = 0): bool
    {
        return $fileModifiedTime > $dbModifiedTime;
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
