<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

final class ExpectSequenceStep
{
    /**
     * @param array<string, bool> $playlistEnabledByRef
     */
    public function __construct(
        public readonly ?string $now,
        public readonly ExpectQueue $expect,
        public readonly array $playlistEnabledByRef = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $playlistEnabledByRef = [];
        foreach (Types::array($data['playlist_enabled_by_ref'] ?? []) as $ref => $isEnabled) {
            $playlistEnabledByRef[(string)$ref] = Types::bool($isEnabled);
        }

        return new self(
            now: Types::stringOrNull($data['now'] ?? null),
            expect: ExpectQueue::fromArray($data),
            playlistEnabledByRef: $playlistEnabledByRef,
        );
    }
}
