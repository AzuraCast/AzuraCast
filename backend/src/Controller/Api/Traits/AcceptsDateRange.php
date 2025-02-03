<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\DateRange;
use App\Utilities\Time;
use App\Utilities\Types;
use Carbon\CarbonImmutable;
use DateTimeZone;

trait AcceptsDateRange
{
    protected function getDateRange(
        ServerRequest $request,
        ?DateTimeZone $tz = null,
        ?DateRange $default = null,
        string $startParam = 'start',
        string $endParam = 'end'
    ): DateRange {
        $tz ??= Time::getUtc();

        $default ??= new DateRange(
            (new CarbonImmutable('-2 weeks', $tz))->startOf('day'),
            CarbonImmutable::now($tz)->endOf('day')
        );

        $queryParams = $request->getQueryParams();
        $startRaw = Types::stringOrNull($queryParams[$startParam] ?? null, true);
        $endRaw = Types::stringOrNull($queryParams[$endParam] ?? null, true);

        if (null === $startRaw && null === $endRaw) {
            return $default;
        }

        $start = (null !== $startRaw)
            ? CarbonImmutable::parse($startRaw, $tz)->setTimezone($tz)->setSecond(0)
            : CarbonImmutable::now($tz)->startOf('day');

        $end = (null !== $endRaw)
            ? CarbonImmutable::parse($endRaw, $tz)->setTimezone($tz)->setSecond(59)
            : CarbonImmutable::now($tz)->endOf('day');

        // If no time is passed for the end date, use the end of the day instead of midnight.
        if (null !== $endRaw) {
            $endDateParts = date_parse($endRaw);
            if ($endDateParts['hour'] === false) {
                $end = $end->endOf('day');
            }
        }

        return ($start < $end)
            ? new DateRange($start, $end)
            : new DateRange($end, $start);
    }
}
