<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Scenario;

use App\Utilities\Types;

final class SimulationExpectations
{
    public function __construct(
        public readonly ?int $measuredPlayCount,
        public readonly ?int $minRepeatIntervalMinutes,
        public readonly ?float $maxRepeatShareNearWindow,
        public readonly ?float $maxRepeatedPairShare,
        public readonly ?float $maxNeverPlayedShare,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            measuredPlayCount: Types::intOrNull($data['measured_play_count'] ?? null),
            minRepeatIntervalMinutes: Types::intOrNull($data['min_repeat_interval_minutes'] ?? null),
            maxRepeatShareNearWindow: Types::floatOrNull($data['max_repeat_share_near_window'] ?? null),
            maxRepeatedPairShare: Types::floatOrNull($data['max_repeated_pair_share'] ?? null),
            maxNeverPlayedShare: Types::floatOrNull($data['max_never_played_share'] ?? null),
        );
    }

    public function isEmpty(): bool
    {
        return $this->measuredPlayCount === null
            && $this->minRepeatIntervalMinutes === null
            && $this->maxRepeatShareNearWindow === null
            && $this->maxRepeatedPairShare === null
            && $this->maxNeverPlayedShare === null;
    }
}
