<?php

declare(strict_types=1);

namespace App\Controller\Api\Traits;

use App\Http\ServerRequest;
use App\Utilities\DateRange;
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

        if (empty($queryParams[$startParam]) || empty($queryParams[$endParam])) {
            return $default;
        }

        $start = CarbonImmutable::parse($queryParams[$startParam], $tz)
            ->setTimezone($tz);

        $end = CarbonImmutable::parse($queryParams[$endParam], $tz)
            ->setTimezone($tz);

        if ($start->equalTo($end)) {
            return $default;
        }

        return new DateRange(
            $start->setSecond(0),
            $end->setSecond(59)
        );
    }
}
