<?php

declare(strict_types=1);

namespace App\Utilities;

use Carbon\CarbonImmutable;
use DateTimeImmutable;

final readonly class DateRange
{
    public function __construct(
        public CarbonImmutable $start,
        public CarbonImmutable $end,
    ) {
    }

    public function contains(?DateTimeImmutable $time): bool
    {
        if (null === $time) {
            return false;
        }

        return CarbonImmutable::instance($time)->between($this->start, $this->end);
    }

    public function isWithin(self $toCompare): bool
    {
        return $this->end >= $toCompare->start
            && $this->start <= $toCompare->end;
    }

    public function format(
        string $format = 'Y-m-d H:i:s',
        string $separator = ' to '
    ): string {
        return $this->start->format($format) . $separator . $this->end->format($format);
    }

    public function __toString(): string
    {
        return $this->format();
    }
}
