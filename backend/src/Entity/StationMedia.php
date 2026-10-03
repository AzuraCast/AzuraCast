<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Interfaces\IdentifiableEntityInterface;
use App\Entity\Interfaces\PathAwareInterface;
use App\Entity\Interfaces\SongInterface;
use App\Flysystem\StationFilesystems;
use App\Media\Enums\MetadataTags;
use App\Media\Metadata;
use App\Media\MetadataInterface;
use App\Utilities\Types;
use Azura\Normalizer\Attributes\DeepNormalize;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute as Serializer;

#[
    ORM\Entity,
    ORM\Table(name: 'station_media'),
    ORM\Index(name: 'search_idx', columns: ['title', 'artist', 'album']),
    ORM\UniqueConstraint(name: 'path_unique_idx', columns: ['path', 'storage_location_id'])
]
final class StationMedia implements
    SongInterface,
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
    public readonly StorageLocation $storage_location;

    public int $storage_location_id {
        get => $this->storage_location->id;
    }

    #[ORM\Column(length: 25, nullable: false)]
    public string $unique_id;

    #[ORM\Column(length: 255, nullable: true)]
    public ?string $genre = null {
        set => $this->truncateNullableString($value);
    }

    #[ORM\Column(type: 'text', nullable: true)]
    public ?string $lyrics = null;

    #[ORM\Column(length: 15, nullable: true)]
    public ?string $isrc = null {
        set => $this->truncateNullableString($value, 15);
    }

    #[ORM\Column(type: 'float', nullable: false)]
    public float $length = 0.0;

    #[ORM\Column(length: 500)]
    public string $path {
        get => $this->path;
        set => $this->truncateString($value, 500);
    }

    #[ORM\Column(nullable: false)]
    public int $mtime;

    #[ORM\Column(nullable: false)]
    public int $uploaded_at;

    #[ORM\Column]
    public int $art_updated_at = 0;

    #[ORM\Column(name: 'extra_metadata', type: 'json', nullable: true)]
    private ?array $extra_metadata_raw = null;

    public StationMediaMetadata $extra_metadata {
        get => new StationMediaMetadata((array)$this->extra_metadata_raw);
        set (StationMediaMetadata|array|null $value) {
            $this->extra_metadata_raw = StationMediaMetadata::merge(
                $this->extra_metadata_raw,
                $value
            );
        }
    }

    /** @var Collection<int, StationPlaylistMedia> */
    #[
        ORM\OneToMany(targetEntity: StationPlaylistMedia::class, mappedBy: 'media'),
        DeepNormalize(true),
        Serializer\MaxDepth(1)
    ]
    public private(set) Collection $playlists;

    /** @var Collection<int, StationMediaCustomField> */
    #[ORM\OneToMany(targetEntity: StationMediaCustomField::class, mappedBy: 'media')]
    public private(set) Collection $custom_fields;

    /** @var Collection<int, PodcastEpisode> */
    #[ORM\OneToMany(targetEntity: PodcastEpisode::class, mappedBy: 'playlist_media')]
    public private(set) Collection $podcast_episodes;

    public function __construct(StorageLocation $storageLocation, string $path)
    {
        $this->storage_location = $storageLocation;
        $this->mtime = $this->uploaded_at = time();
        $this->unique_id = bin2hex(random_bytes(12));
        $this->path = $path;

        $this->playlists = new ArrayCollection();
        $this->custom_fields = new ArrayCollection();
        $this->podcast_episodes = new ArrayCollection();
    }

    /**
     * @return string[]
     */
    public function getRelatedFilePaths(): array
    {
        return [
            self::getArtPath($this->unique_id),
            self::getWaveformPath($this->unique_id),
        ];
    }

    /**
     * Get the length with cue-in and cue-out points included.
     */
    public function getCalculatedLength(): float
    {
        $length = $this->length;
        $extraMeta = $this->extra_metadata;

        $cueOut = $extraMeta->cue_out;
        if ($cueOut > 0) {
            $lengthRemoved = $length - $cueOut;
            $length -= $lengthRemoved;
        }

        $cueIn = $extraMeta->cue_in;
        if ($cueIn > 0) {
            $length -= $cueIn;
        }

        return $length;
    }

    /**
     * Indicates whether this media is a part of any "requestable" playlists.
     */
    public function isRequestable(): bool
    {
        foreach ($this->playlists as $playlistItem) {
            $playlist = $playlistItem->playlist;
            /** @var StationPlaylist $playlist */
            if ($playlist->isRequestable()) {
                return true;
            }
        }

        return false;
    }

    public function fromMetadata(MetadataInterface $metadata): void
    {
        $this->length = $metadata->getDuration();

        $tags = $metadata->getKnownTags();

        if (isset($tags['title'])) {
            $this->title = Types::stringOrNull($tags['title']);
        }
        if (isset($tags['artist'])) {
            $this->artist = Types::stringOrNull($tags['artist']);
        }
        if (isset($tags['album'])) {
            $this->album = Types::stringOrNull($tags['album']);
        }
        if (isset($tags['genre'])) {
            $this->genre = Types::stringOrNull($tags['genre']);
        }
        if (isset($tags['unsynchronised_lyric'])) {
            $this->lyrics = Types::stringOrNull($tags['unsynchronised_lyric']);
        }
        if (isset($tags['isrc'])) {
            $this->isrc = Types::stringOrNull($tags['isrc']);
        }

        $this->extra_metadata = $metadata->getExtraTags();
        $this->updateMetaFields();
    }

    public function toMetadata(): MetadataInterface
    {
        $metadata = new Metadata();
        $metadata->setDuration($this->length);

        /** @var array<value-of<MetadataTags>, mixed> $tags */
        $tags = array_filter(
            [
                'title' => $this->title,
                'artist' => $this->artist,
                'album' => $this->album,
                'genre' => $this->genre,
                'unsynchronised_lyric' => $this->lyrics,
                'isrc' => $this->isrc,
            ]
        );

        /** @var array<string, mixed> $extraTags */
        $extraTags = [];
        $seenExtraKeys = [];

        foreach ($this->custom_fields as $customField) {
            $autoAssign = $customField->field->auto_assign;
            $value = $customField->value;

            if (
                $autoAssign === null
                || $value === null
                || trim($value) === ''
            ) {
                continue;
            }

            $tagEnum = MetadataTags::getTag($autoAssign);
            if ($tagEnum !== null) {
                $tags[$tagEnum->value] ??= $value;
                continue;
            }

            $lowerKey = mb_strtolower($autoAssign);
            if (isset($seenExtraKeys[$lowerKey])) {
                continue;
            }

            $seenExtraKeys[$lowerKey] = true;
            $extraTags[$autoAssign] = $value;
        }

        // Sorted so reorders of custom fields don't look like metadata changed
        ksort($tags);

        $extraTags = array_replace($extraTags, $this->extra_metadata->toArray() ?? []);
        ksort($extraTags);

        $metadata->setKnownTags($tags);
        $metadata->setExtraTags($extraTags);

        return $metadata;
    }

    public function __clone(): void
    {
        $this->playlists = new ArrayCollection();
        $this->custom_fields = new ArrayCollection();
        $this->podcast_episodes = new ArrayCollection();
    }

    public function __toString(): string
    {
        return 'StationMedia ' . $this->id . ': ' . $this->artist . ' - ' . $this->title;
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
