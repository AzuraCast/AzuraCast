<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

/**
 * A single cued and not yet played queue entry in a scenario's "cued_media" runtime section
 */
final class CuedMediaEntry
{
    public function __construct(
        public readonly string $mediaRef,
        public readonly string $playlistRef,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            mediaRef: Types::string($data['media_ref'] ?? null),
            playlistRef: Types::string($data['playlist_ref'] ?? null),
        );
    }
}
