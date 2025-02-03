<?php

declare(strict_types=1);

namespace App\Entity\Traits;

use App\Utilities\Time;
use Carbon\CarbonImmutable;
use DateTimeInterface;

trait HandleDateTimes
{
    /**
     * Given either a Unix timestamp or an existing DateTime of any variety, returns
     * a CarbonImmutable instance set (or shifted) to UTC.
     *
     * @param float|string|int|DateTimeInterface $input
     * @return CarbonImmutable
     */
    protected function toUtcCarbonImmutable(float|string|int|DateTimeInterface $input): CarbonImmutable
    {
        if (is_numeric($input)) {
            CarbonImmutable::createFromTimestampUTC($input);
        }

        $utc = Time::getUtc();

        $time = ($input instanceof DateTimeInterface)
            ? CarbonImmutable::instance($input)
            : CarbonImmutable::parse($input, $utc);

        if (!$time->isUtc()) {
            $time = $time->shiftTimezone($utc);
        }

        return $time;
    }

    protected function toNullableUtcCarbonImmutable(float|string|int|DateTimeInterface|null $input): ?CarbonImmutable
    {
        if (null === $input) {
            return null;
        }

        return $this->toUtcCarbonImmutable($input);
    }
}
