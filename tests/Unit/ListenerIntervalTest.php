<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Listener;
use Carbon\CarbonImmutable;
use Codeception\Test\Unit;
use DateTimeZone;

class ListenerIntervalTest extends Unit
{
    public function testListenerIntervals(): void
    {
        $utc = new DateTimeZone('UTC');

        $intervals = [
            [
                'start' => CarbonImmutable::parse('2019-12-01 00:00:00', $utc)->getTimestamp(),
                'end' => CarbonImmutable::parse('2019-12-01 02:05:00', $utc)->getTimestamp(),
            ],
            [
                'start' => CarbonImmutable::parse('2019-12-01 00:00:00', $utc)->getTimestamp(),
                'end' => CarbonImmutable::parse('2019-12-01 03:00:00', $utc)->getTimestamp(),
            ],
            [
                'start' => CarbonImmutable::parse('2019-12-01 05:00:00', $utc)->getTimestamp(),
                'end' => CarbonImmutable::parse('2019-12-01 07:05:00', $utc)->getTimestamp(),
            ],
            [
                'start' => CarbonImmutable::parse('2019-12-01 05:05:30', $utc)->getTimestamp(),
                'end' => CarbonImmutable::parse('2019-12-01 08:00:00', $utc)->getTimestamp(),
            ],
        ];

        $expected = 6 * 60 * 60;
        self::assertEquals($expected, Listener::getListenerSeconds($intervals));
    }
}
