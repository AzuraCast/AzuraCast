<?php

declare(strict_types=1);

namespace Unit\AutoDJ;

use App\Tests\AutoDJ\InMemoryAutoDjHarness;
use App\Tests\AutoDJ\InMemoryAutoDjHarnessFactory;
use App\Tests\AutoDJ\Scenario\ScenarioRuntime;
use App\Utilities\DateRange;
use Carbon\CarbonImmutable;
use Codeception\Test\Unit;
use RuntimeException;

use const JSON_THROW_ON_ERROR;

/**
 * Test for Scheduler::isPlaylistBlockedByGroupSchedule() which controls the
 * "scheduled outside its group's window" warning in the station Schedule View.
 */
final class PlaylistGroupScheduleConflictTest extends Unit
{
    private function harness(): InMemoryAutoDjHarness
    {
        $path = codecept_data_dir('autodj/scheduling/group-schedule-conflict.dump.json');

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read fixture file "%s".', $path));
        }

        /** @var array<string, mixed> $dump */
        $dump = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return (new InMemoryAutoDjHarnessFactory())->create($dump, ScenarioRuntime::fromArray([]));
    }

    private function window(string $start, string $end): DateRange
    {
        return new DateRange(
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end)
        );
    }

    public function testMemberScheduledOutsideGroupWindowIsBlocked(): void
    {
        $harness = $this->harness();
        $playlist = $harness->entities->playlistForRef('outside');

        self::assertTrue(
            $harness->scheduler->isPlaylistBlockedByGroupSchedule(
                $playlist,
                $this->window('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testMemberScheduledInsideGroupWindowIsNotBlocked(): void
    {
        $harness = $this->harness();
        $playlist = $harness->entities->playlistForRef('inside');

        self::assertFalse(
            $harness->scheduler->isPlaylistBlockedByGroupSchedule(
                $playlist,
                $this->window('2018-01-15T12:15:00+00:00', '2018-01-15T12:45:00+00:00')
            )
        );
    }

    public function testStandaloneScheduledPlaylistIsNeverBlocked(): void
    {
        $harness = $this->harness();
        $playlist = $harness->entities->playlistForRef('standalone');

        self::assertFalse(
            $harness->scheduler->isPlaylistBlockedByGroupSchedule(
                $playlist,
                $this->window('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testMemberOfUnscheduledGroupIsNotBlocked(): void
    {
        $harness = $this->harness();
        $playlist = $harness->entities->playlistForRef('open_member');

        self::assertFalse(
            $harness->scheduler->isPlaylistBlockedByGroupSchedule(
                $playlist,
                $this->window('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testDeeplyNestedMemberIsBlockedByAncestorGroupWindow(): void
    {
        $harness = $this->harness();
        $playlist = $harness->entities->playlistForRef('deep_member');

        self::assertTrue(
            $harness->scheduler->isPlaylistBlockedByGroupSchedule(
                $playlist,
                $this->window('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }
}
