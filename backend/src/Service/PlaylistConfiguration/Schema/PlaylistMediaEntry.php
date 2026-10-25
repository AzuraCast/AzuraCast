<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration\Schema;

use App\Utilities\Types;
use JsonSerializable;

/**
 * @phpstan-type PlaylistMediaShape array{
 *     media_ref: string,
 *     weight: int,
 *     folder_ref: ?string
 * }
 */
final class PlaylistMediaEntry implements JsonSerializable
{
    public function __construct(
        public readonly string $mediaRef,
        public readonly int $weight,
        public readonly ?string $folderRef,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mediaRef: Types::string($data['media_ref'] ?? null),
            weight: Types::int($data['weight'] ?? null),
            folderRef: Types::stringOrNull($data['folder_ref'] ?? null),
        );
    }

    /**
     * @return PlaylistMediaShape
     */
    public function jsonSerialize(): mixed
    {
        return [
            'media_ref' => $this->mediaRef,
            'weight' => $this->weight,
            'folder_ref' => $this->folderRef,
        ];
    }
}
