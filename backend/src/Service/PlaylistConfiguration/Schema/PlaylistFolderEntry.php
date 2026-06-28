<?php

declare(strict_types=1);

namespace App\Service\PlaylistConfiguration\Schema;

use App\Utilities\Types;
use JsonSerializable;

final class PlaylistFolderEntry implements JsonSerializable
{
    public function __construct(
        public readonly string $ref,
        public readonly string $path,
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
        );
    }

    public function jsonSerialize(): mixed
    {
        return [
            'ref' => $this->ref,
            'path' => $this->path,
        ];
    }
}
