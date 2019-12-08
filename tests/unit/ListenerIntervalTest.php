<?php

use App\Entity;
use Cake\Chronos\Chronos;

class ListenerIntervalTest extends \Codeception\Test\Unit
{
    public function testListenerIntervals()
    {
        $utc = new \DateTimeZone('UTC');

        $intervals = [
            [
                'start' => Chronos::parse('2019-12-01 00:00:00', $utc)->getTimestamp(),
                'end' => Chronos::parse('2019-12-01 02:05:00', $utc)->getTimestamp(),
            ],
            [
                'start' => Chronos::parse('2019-12-01 00:00:00', $utc)->getTimestamp(),
                'end' => Chronos::parse('2019-12-01 03:00:00', $utc)->getTimestamp(),
            ],
            [
                'start' => Chronos::parse('2019-12-01 05:00:00', $utc)->getTimestamp(),
                'end' => Chronos::parse('2019-12-01 07:05:00', $utc)->getTimestamp(),
            ],
            [
                'start' => Chronos::parse('2019-12-01 05:05:30', $utc)->getTimestamp(),
                'end' => Chronos::parse('2019-12-01 08:00:00', $utc)->getTimestamp(),
            ],
        ];

        $expected = 6 * 60 * 60;
        $this->assertEquals($expected, Entity\Listener::getListenerSeconds($intervals));
    }
}
