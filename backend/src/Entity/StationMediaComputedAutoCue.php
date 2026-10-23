<?php

declare(strict_types=1);

namespace App\Entity;

use App\Doctrine\AbstractArrayEntity;
use App\Utilities\Time;

final class StationMediaComputedAutoCue extends AbstractArrayEntity
{
    private const int MAX_AGE_DAYS = 7;

    public int $mtime = 0;

    public int $computed_at = 0;

    /** @var array<string, float> */
    public array $values = [];

    /**
     * @param array<array-key, mixed> $computedValues
     */
    public static function fromComputedValues(int $mtime, array $computedValues): self
    {
        return new self([
            'mtime' => $mtime,
            'computed_at' => Time::nowUtc()->getTimestamp(),
            'values' => array_filter(
                new StationMediaMetadata($computedValues)->toArray() ?? [],
                fn(?float $value): bool => $value !== null
            ),
        ]);
    }

    public function isValidFor(int $mtime): bool
    {
        return $this->mtime === $mtime && !$this->isExpired();
    }

    private function isExpired(): bool
    {
        return $this->computed_at <= Time::nowUtc()->subDays(self::MAX_AGE_DAYS)->getTimestamp();
    }
}
