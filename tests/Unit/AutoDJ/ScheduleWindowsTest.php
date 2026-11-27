<?php

declare(strict_types=1);

namespace Unit\AutoDJ;

use App\Tests\AutoDJ\DumpLoader;
use App\Tests\AutoDJ\InMemoryAutoDjHarness;
use App\Tests\AutoDJ\InMemoryAutoDjHarnessFactory;
use App\Tests\AutoDJ\Scenario\ScenarioRuntime;
use App\Utilities\DateRange;
use Carbon\CarbonImmutable;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

/**
 * Tests the schedule windows shown in the station Schedule View, the public schedule and the
 * upcoming schedule.
 *
 * The windows shown must match the times the Scheduler actually plays a schedule.
 */
final class ScheduleWindowsTest extends Unit
{
    private function createHarness(string $fixture): InMemoryAutoDjHarness
    {
        return (new InMemoryAutoDjHarnessFactory())->create(
            DumpLoader::loadDump('scheduling/' . $fixture),
            ScenarioRuntime::fromArray([])
        );
    }

    /**
     * @return string[]
     */
    private function getWindows(
        InMemoryAutoDjHarness $harness,
        string $playlistRef,
        string $start,
        string $end
    ): array {
        $playlist = $harness->entities->playlistForRef($playlistRef);

        $schedule = $playlist->schedule_items->first();
        self::assertNotFalse($schedule, sprintf('Playlist "%s" has no schedule item.', $playlistRef));

        $windows = $harness->scheduler->getScheduleWindowsInRange(
            $schedule,
            $playlist->station->getTimezoneObject(),
            new DateRange(CarbonImmutable::parse($start), CarbonImmutable::parse($end))
        );

        return array_map(
            static fn(DateRange $window): string => $window->format('Y-m-d H:i', ' - '),
            $windows
        );
    }

    public function testMultiNightOvernightScheduleIncludesLastNight(): void
    {
        $harness = $this->createHarness('schedule-date-range');

        self::assertSame(
            [
                '2018-06-01 22:00 - 2018-06-02 04:00',
                '2018-06-02 22:00 - 2018-06-03 04:00',
                '2018-06-03 22:00 - 2018-06-04 04:00',
            ],
            $this->getWindows($harness, 'multinight', '2018-05-28T00:00:00+00:00', '2018-06-10T23:59:59+00:00')
        );
    }

    public function testLastNightOfMultiNightScheduleRunsIntoFollowingDay(): void
    {
        $harness = $this->createHarness('schedule-date-range');

        self::assertSame(
            [
                '2018-06-03 22:00 - 2018-06-04 04:00',
            ],
            $this->getWindows($harness, 'multinight', '2018-06-04T00:00:00+00:00', '2018-06-04T23:59:59+00:00')
        );
    }

    public function testSingleNightOvernightScheduleCoversOneNight(): void
    {
        $harness = $this->createHarness('schedule-date-range');

        self::assertSame(
            [
                '2018-06-01 22:00 - 2018-06-02 04:00',
            ],
            $this->getWindows($harness, 'night', '2018-05-28T00:00:00+00:00', '2018-06-10T23:59:59+00:00')
        );
    }

    public function testDatedDaytimeScheduleCoversEachDayInRange(): void
    {
        $harness = $this->createHarness('schedule-date-range');

        self::assertSame(
            [
                '2018-06-01 10:00 - 2018-06-01 14:00',
                '2018-06-02 10:00 - 2018-06-02 14:00',
                '2018-06-03 10:00 - 2018-06-03 14:00',
            ],
            $this->getWindows($harness, 'event', '2018-05-28T00:00:00+00:00', '2018-06-10T23:59:59+00:00')
        );
    }

    public function testOvernightScheduleOnlyStartsOnSelectedDays(): void
    {
        $harness = $this->createHarness('overnight-window');

        self::assertSame(
            [
                '2018-01-15 22:00 - 2018-01-16 04:00',
                '2018-01-16 22:00 - 2018-01-17 04:00',
                '2018-01-17 22:00 - 2018-01-18 04:00',
            ],
            $this->getWindows($harness, 'overnight', '2018-01-15T00:00:00+00:00', '2018-01-21T23:59:59+00:00')
        );
    }

    /**
     * @return array<string, array{string, string, string, string}>
     */
    public static function playbackParityProvider(): array
    {
        return [
            'dated daytime schedule' => [
                'schedule-date-range',
                'event',
                '2018-05-30T00:00:00+00:00',
                '2018-06-06T00:00:00+00:00',
            ],
            'single-night overnight schedule' => [
                'schedule-date-range',
                'night',
                '2018-05-30T00:00:00+00:00',
                '2018-06-06T00:00:00+00:00',
            ],
            'multi-night overnight schedule' => [
                'schedule-date-range',
                'multinight',
                '2018-05-30T00:00:00+00:00',
                '2018-06-06T00:00:00+00:00',
            ],
            'overnight schedule on selected days' => [
                'overnight-window',
                'overnight',
                '2018-01-13T00:00:00+00:00',
                '2018-01-21T00:00:00+00:00',
            ],
            'schedule in non-UTC station time zone' => [
                'timezone-berlin',
                'morning',
                '2018-01-13T00:00:00+00:00',
                '2018-01-17T00:00:00+00:00',
            ],
        ];
    }

    /**
     * Every 30 minutes across the range, the schedule must play exactly when one of the
     * windows shown for it contains that time.
     */
    #[DataProvider('playbackParityProvider')]
    public function testScheduleWindowsMatchPlayback(
        string $fixture,
        string $playlistRef,
        string $start,
        string $end
    ): void {
        $harness = $this->createHarness($fixture);

        $playlist = $harness->entities->playlistForRef($playlistRef);
        $tz = $playlist->station->getTimezoneObject();

        $schedule = $playlist->schedule_items->first();
        self::assertNotFalse($schedule, sprintf('Playlist "%s" has no schedule item.', $playlistRef));

        $dateRange = new DateRange(CarbonImmutable::parse($start), CarbonImmutable::parse($end));
        $windows = $harness->scheduler->getScheduleWindowsInRange($schedule, $tz, $dateRange);

        for ($now = $dateRange->start; $now <= $dateRange->end; $now = $now->addMinutes(30)) {
            $isInWindow = array_any(
                $windows,
                static fn(DateRange $window): bool => $window->contains($now)
            );

            $isPlaying = $harness->scheduler->shouldSchedulePlayNow(
                $schedule,
                $tz,
                $now,
                excludeSpecialRules: true
            );

            self::assertSame(
                $isPlaying,
                $isInWindow,
                sprintf(
                    'At %s the schedule %s, but the schedule windows %s it.',
                    $now->toIso8601String(),
                    $isPlaying ? 'plays' : 'does not play',
                    $isInWindow ? 'show' : 'do not show'
                )
            );
        }
    }
}
