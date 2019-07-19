<?php
use App\Entity;

class StationPlaylistTest extends \Codeception\Test\Unit
{
    /**
     * @var \UnitTester
     */
    protected $tester;

    public function testScheduledPlaylist()
    {
        /** @var Entity\Station $station */
        $station = Mockery::mock(Entity\Station::class);

        $playlist = new Entity\StationPlaylist($station);

        // Sample playlist that plays from 10PM to 4AM the next day.
        $playlist->setType(Entity\StationPlaylist::TYPE_SCHEDULED);
        $playlist->setScheduleStartTime(2200);
        $playlist->setScheduleEndTime(400);
        $playlist->setScheduleDays([1, 2, 3]); // Monday, Tuesday, Wednesday

        $utc = new \DateTimeZone('UTC');
        $test_monday = \Cake\Chronos\Chronos::create(2018, 1, 15, 0, 0, 0, $utc);
        $test_thursday = \Cake\Chronos\Chronos::create(2018, 1, 18, 0, 0, 0, $utc);

        // Sanity check: Jan 15, 2018 is a Monday, and Jan 18, 2018 is a Thursday.
        $this->assertTrue($test_monday->isMonday());
        $this->assertTrue($test_thursday->isThursday());

        // Playlist SHOULD play Monday evening at 10:30PM.
        $test_time = $test_monday->setTime(22, 30);
        $this->assertTrue($playlist->shouldPlayNowScheduled($test_time));

        // Playlist SHOULD play Thursday morning at 3:00AM.
        $test_time = $test_thursday->setTime(3, 0);
        $this->assertTrue($playlist->shouldPlayNowScheduled($test_time));

        // Playlist SHOULD NOT play Monday morning at 3:00AM.
        $test_time = $test_monday->setTime(3, 0);
        $this->assertFalse($playlist->shouldPlayNowScheduled($test_time));

        // Playlist SHOULD NOT play Thursday evening at 10:30PM.
        $test_time = $test_thursday->setTime(22, 30);
        $this->assertFalse($playlist->shouldPlayNowScheduled($test_time));
    }

    public function testOncePerHourPlaylist()
    {
        /** @var Entity\Station $station */
        $station = Mockery::mock(Entity\Station::class);

        $playlist = new Entity\StationPlaylist($station);
        $playlist->setType(Entity\StationPlaylist::TYPE_ONCE_PER_HOUR);
        $playlist->setPlayPerHourMinute(50);

        $utc = new \DateTimeZone('UTC');
        $test_day = \Cake\Chronos\Chronos::create(2018, 1, 15, 0, 0, 0, $utc);

        // Playlist SHOULD try to play at 11:59 PM.
        $test_time = $test_day->setTime(23, 59);
        $this->assertTrue($playlist->shouldPlayNowPerHour($test_time));

        // Playlist SHOULD try to play at 12:04 PM.
        $test_time = $test_day->setTime(12, 4);
        $this->assertTrue($playlist->shouldPlayNowPerHour($test_time));

        // Playlist SHOULD NOT try to play at 11:49 PM.
        $test_time = $test_day->setTime(23, 49);
        $this->assertFalse($playlist->shouldPlayNowPerHour($test_time));

        // Playlist SHOULD NOT try to play at 12:06 PM.
        $test_time = $test_day->setTime(12, 6);
        $this->assertFalse($playlist->shouldPlayNowPerHour($test_time));
    }
}
