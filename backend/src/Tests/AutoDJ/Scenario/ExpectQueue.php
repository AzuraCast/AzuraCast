<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Tests\AutoDJ\Scenario\Enums\ExpectQueueMode;
use App\Utilities\Types;

/**
 * Describes the next track the QueueBuilder should select
 */
final class ExpectQueue
{
    /**
     * @param string[] $mediaAnyOf Keyed by media ref
     */
    public function __construct(
        public readonly ExpectQueueMode $mode,
        public readonly bool $interrupting,
        public readonly ?string $playlistRef,
        public readonly ?string $mediaRef,
        public readonly array $mediaAnyOf,
        public readonly bool $distinct,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $mode = Types::stringOrNull($data['mode'] ?? null);

        return new self(
            mode: ExpectQueueMode::tryFrom($mode ?? '') ?? ExpectQueueMode::Exact,
            interrupting: Types::bool($data['interrupting'] ?? false),
            playlistRef: Types::stringOrNull($data['playlist_ref'] ?? null),
            mediaRef: Types::stringOrNull($data['media_ref'] ?? null),
            mediaAnyOf: array_map(
                static fn(mixed $ref): string => Types::string($ref),
                Types::array($data['media_any_of'] ?? [])
            ),
            distinct: Types::bool($data['distinct'] ?? false),
        );
    }
}
