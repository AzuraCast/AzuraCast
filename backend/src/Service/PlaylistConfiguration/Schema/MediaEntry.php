<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration\Schema;

use App\Utilities\Types;
use JsonSerializable;

final class MediaEntry implements JsonSerializable
{
    public function __construct(
        public readonly string $ref,
        public readonly string $path,
        public readonly string $uniqueId,
        public readonly float $length,
        public readonly ?string $artist,
        public readonly ?string $title,
        public readonly ?string $album,
        public readonly ?string $genre,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            ref: Types::string($data['ref'] ?? null),
            path: Types::string($data['path'] ?? null),
            uniqueId: Types::string($data['unique_id'] ?? null),
            length: Types::float($data['length'] ?? null),
            artist: Types::string(
                input: $data['artist'] ?? null,
                countEmptyAsNull: true
            ),
            title: Types::string(
                input: $data['title'] ?? null,
                countEmptyAsNull: true
            ),
            album: Types::string(
                input: $data['album'] ?? null,
                countEmptyAsNull: true
            ),
            genre: Types::string(
                input: $data['genre'] ?? null,
                countEmptyAsNull: true
            ),
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'ref' => $this->ref,
            'path' => $this->path,
            'unique_id' => $this->uniqueId,
            'length' => $this->length,
            'artist' => $this->artist,
            'title' => $this->title,
            'album' => $this->album,
            'genre' => $this->genre,
        ];
    }
}
