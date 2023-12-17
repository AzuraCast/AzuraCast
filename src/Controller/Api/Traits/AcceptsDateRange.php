<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\DateRange;
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
        $tz ??= new DateTimeZone('UTC');

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
            ? CarbonImmutable::parse($startRaw, $tz)->setTimezone($tz)
            : CarbonImmutable::now($tz)->startOf('day');

        $end = (null !== $endRaw)
            ? CarbonImmutable::parse($endRaw, $tz)->setTimezone($tz)
            : CarbonImmutable::now($tz)->endOf('day');

        $start = $start->setSecond(0);
        $end = $end->setSecond(59);

        return ($start < $end)
            ? new DateRange($start, $end)
            : new DateRange($end, $start);
    }
}
