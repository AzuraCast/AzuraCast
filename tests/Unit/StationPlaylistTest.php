<?php

declare(strict_types=1);

namespace Unit;

use App\Entity\Enums\PlaylistTypes;
use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationSchedule;
use App\Radio\AutoDJ\Scheduler;
use App\Radio\AutoDJ\SchedulerContext;
use App\Tests\Module;
use Carbon\CarbonImmutable;
use Codeception\Test\Unit;
use DateTimeZone;
use Mockery;
use UnitTester;

class StationPlaylistTest extends Unit
{
    protected UnitTester $tester;

    protected Scheduler $scheduler;

    protected function _inject(Module $testsModule): void
    {
        $di = $testsModule->container;
        $this->scheduler = $di->get(Scheduler::class);
    }

    public function testScheduledPlaylist(): void
    {
        /** @var Station $station */
        $station = Mockery::mock(Station::class);

        $playlist = new StationPlaylist($station);
        $playlist->setName('Test Playlist');

        // Sample playlist that plays from 10PM to 4AM the next day.
        $scheduleEntry = new StationSchedule($playlist);
        $scheduleEntry->setStartTime(2200);
        $scheduleEntry->setEndTime(400);
        $scheduleEntry->setDays([1, 2, 3]); // Monday, Tuesday, Wednesday

        $playlist->getScheduleItems()->add($scheduleEntry);

        $utc = new DateTimeZone('UTC');
        $testMonday = CarbonImmutable::create(2018, 1, 15, 0, 0, 0, $utc);
        $testThursday = CarbonImmutable::create(2018, 1, 18, 0, 0, 0, $utc);

        // Sanity check: Jan 15, 2018 is a Monday, and Jan 18, 2018 is a Thursday.
        self::assertTrue($testMonday->isMonday());
        self::assertTrue($testThursday->isThursday());
        $ctx = new SchedulerContext($playlist);
        // Playlist SHOULD play Monday evening at 10:30PM.
        $ctx->expectedPlayTime = $testMonday->setTime(22, 30);
        self::assertTrue($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Playlist SHOULD play Thursday morning at 3:00AM.
        $ctx->expectedPlayTime = $testThursday->setTime(3, 0);
        self::assertTrue($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Playlist SHOULD NOT play Monday morning at 3:00AM.
        $ctx->expectedPlayTime = $testMonday->setTime(3, 0);
        self::assertFalse($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Playlist SHOULD NOT play Thursday evening at 10:30PM.
        $ctx->expectedPlayTime = $testThursday->setTime(22, 30);
        self::assertFalse($this->scheduler->shouldPlaylistPlayNow($ctx));
    }
    /**
     * This test will not pass.
     * The scheduler was updated to query recent songs instead of relying on played_at.
     */
    public function testOncePerXMinutesPlaylist()
    {
        /** @var Station $station */
        $station = Mockery::mock(Station::class);

        $playlist = new StationPlaylist($station);
        $playlist->setName('Test Playlist');
        $playlist->setType(PlaylistTypes::OncePerXMinutes);
        $playlist->setPlayPerMinutes(30);

        $utc = new DateTimeZone('UTC');
        $ctx = new SchedulerContext($playlist);
        $testDay = CarbonImmutable::create(2018, 1, 15, 0, 0, 0, $utc);
        $ctx->expectedTime = $testDay;
        // Last played 20 minutes ago, SHOULD NOT play again.
        $lastPlayed = $testDay->addMinutes(0 - 20);
        $playlist->setPlayedAt($lastPlayed->getTimestamp());

        self::assertFalse($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Last played 40 minutes ago, SHOULD play again.
        $lastPlayed = $testDay->addMinutes(0 - 40);
        $playlist->setPlayedAt($lastPlayed->getTimestamp());

        self::assertTrue($this->scheduler->shouldPlaylistPlayNow($ctx));
    }

    public function testOncePerHourPlaylist()
    {
        /** @var Station $station */
        $station = Mockery::mock(Station::class);

        $playlist = new StationPlaylist($station);
        $playlist->setName('Test Playlist');
        $playlist->setType(PlaylistTypes::OncePerHour);
        $playlist->setPlayPerHourMinute(50);

        $utc = new DateTimeZone('UTC');
        $ctx = new SchedulerContext($playlist);
        $testDay = CarbonImmutable::create(2018, 1, 15, 0, 0, 0, $utc);
        $ctx->expectedPlayTime = $testDay;

        // Playlist SHOULD try to play at 11:59 PM.
        $ctx->expectedPlayTime = $testDay->setTime(23, 59);
        self::assertTrue($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Playlist SHOULD try to play at 12:04 PM.
        $ctx->expectedPlayTime = $testDay->setTime(12, 4);
        self::assertTrue($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Playlist SHOULD NOT try to play at 11:49 PM.
        $ctx->expectedPlayTime = $testDay->setTime(23, 49);
        self::assertFalse($this->scheduler->shouldPlaylistPlayNow($ctx));

        // Playlist SHOULD NOT try to play at 12:06 PM.
        $ctx->expectedPlayTime = $testDay->setTime(12, 6);
        self::assertFalse($this->scheduler->shouldPlaylistPlayNow($ctx));
    }
}
