<?php

declare(strict_types=1);

namespace App\Utilities;

use Carbon\CarbonInterface;

final class DateRange
{
    public function __construct(
        private readonly CarbonInterface $start,
        private readonly CarbonInterface $end,
    ) {
    }

    public function getStart(): CarbonInterface
    {
        return $this->start;
    }

    public function getStartTimestamp(): int
    {
        return $this->start->getTimestamp();
    }

    public function getEnd(): CarbonInterface
    {
        return $this->end;
    }

    public function getEndTimestamp(): int
    {
        return $this->end->getTimestamp();
    }

    public function contains(CarbonInterface $time): bool
    {
        return $time->between($this->start, $this->end);
    }
}
