<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

final class ExpectSequenceStep
{
    public function __construct(
        public readonly ?string $now,
        public readonly ExpectQueue $expect,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            now: Types::stringOrNull($data['now'] ?? null),
            expect: ExpectQueue::fromArray($data),
        );
    }
}
