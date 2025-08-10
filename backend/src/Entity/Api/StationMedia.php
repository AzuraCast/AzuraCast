<?php

declare(strict_types=1);

namespace App\Entity\Api;

use App\Entity\Api\Traits\HasLinks;
use App\Entity\Api\Traits\HasSongFields;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: 'Api_StationMedia',
    type: 'object'
)]
class StationMedia
{
    use HasSongFields;
    use HasLinks;

    #[OA\Property(
        description: "The media's identifier.",
        example: 1
    )]
    public int $id;

    #[OA\Property(
        description: "A unique identifier for this specific media item in the station's library. " .
            "Each entry in the media table has a unique ID, even if it refers to a song that exists elsewhere.",
        example: "69b536afc7ebbf16457b8645"
    )]
    public string $unique_id = '';

    #[OA\Property(
        description: "The media file's 32-character unique song identifier hash. This hash is based " .
        "on track metadata, so the same song uploaded multiple times will have the same `song_id`.",
        example: "9f33bbc912c19603e51be8e0987d076b"
    )]
    public string $song_id = '';

    #[OA\Property(
        description: "URL to the album art.",
        example: "https://picsum.photos/1200/1200"
    )]
    public string $art = '';

    #[OA\Property(
        description: "The relative path of the media file.",
        example: "test.mp3"
    )]
    public string $path;

    #[OA\Property(
        description: "The UNIX timestamp when the database was last modified.",
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $mtime;

    #[OA\Property(
        description: "The UNIX timestamp when the item was first imported into the database.",
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $uploaded_at;

    #[OA\Property(
        description: "The latest time (UNIX timestamp) when album art was updated.",
        example: OpenApi::SAMPLE_TIMESTAMP
    )]
    public int $art_updated_at = 0;

    #[OA\Property(
        description: "The song duration in seconds.",
        example: 240.00
    )]
    public float $length = 0.0;

    #[OA\Property(
        description: "The formatted song duration (in mm:ss format)",
        example: "4:00"
    )]
    public string $length_text = '0:00';

    #[OA\Property(
        description: "An object containing all custom fields, with the key being the value name.",
    )]
    public HashMap $custom_fields;

    #[OA\Property]
    public HashMap $extra_metadata;

    /**
     * @var StationMediaPlaylist[]
     */
    #[OA\Property(type: "array", items: new OA\Items(
        oneOf: [
            new OA\Schema(ref: StationMediaPlaylist::class, readOnly: true),
            new OA\Schema(type: 'integer', writeOnly: true),
        ],
    ))]
    public array $playlists = [];

    public static function fromArray(
        array $row,
        array $extraMetadata = [],
        array $customFields = [],
        array $playlists = []
    ): self {
        $media = new self();

        $media->id = $row['id'];
        $media->unique_id = $row['unique_id'];
        $media->path = $row['path'];

        $media->song_id = $row['song_id'];
        $media->title = $row['title'];
        $media->artist = $row['artist'];
        $media->text = ($media->artist ?? '') . ' - ' . ($media->title ?? '');
        $media->album = $row['album'];
        $media->genre = $row['genre'];
        $media->isrc = $row['isrc'];
        $media->lyrics = $row['lyrics'] ?? null;

        $media->length = Types::int($row['length']);
        $media->length_text = self::getLengthText($row['length']);
        $media->art_updated_at = $row['art_updated_at'];

        $media->mtime = Types::int($row['mtime'] ?? 0);
        $media->uploaded_at = Types::int($row['uploaded_at'] ?? 0);

        $media->extra_metadata = new HashMap($extraMetadata);
        $media->custom_fields = new HashMap($customFields);
        $media->playlists = $playlists;

        return $media;
    }

    public static function getLengthText(string|float $length): string
    {
        $lengthInt = (int)floor(Types::float($length));
        $lengthMin = floor($lengthInt / 60);
        $lengthSec = $lengthInt % 60;

        return $lengthMin . ':' . str_pad((string)$lengthSec, 2, '0', STR_PAD_LEFT);
    }
}
