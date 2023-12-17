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
        $endRaw = Types::stringOrNull($queryParams[$endParam] ?? null, true) ?? $startRaw;

        if (null === $startRaw) {
            return $default;
        }

        $start = CarbonImmutable::parse($startRaw, $tz)->setTimezone($tz);
        $end = CarbonImmutable::parse($endRaw, $tz)->setTimezone($tz);

        return new DateRange(
            $start->setSecond(0),
            $end->setSecond(59)
        );
    }
}
