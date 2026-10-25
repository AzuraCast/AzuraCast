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
 * Test for Scheduler::isPlaylistFullyCoveredByGroupSchedule() and
 * Scheduler::isPlaylistCoveredByGroupScheduleAt(), which drive the "plays via its
 * group during this window" badge in the station Schedule View and the suppression
 * of a member's standalone play while an ancestor group is schedule-active.
 */
final class PlaylistGroupScheduleCoverageTest extends Unit
{
    private function createHarness(): InMemoryAutoDjHarness
    {
        $path = codecept_data_dir('autodj/scheduling/group-schedule-coverage.dump.json');

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw new RuntimeException(sprintf('Could not read fixture file "%s".', $path));
        }

        /** @var array<string, mixed> $dump */
        $dump = json_decode($contents, true, 512, JSON_THROW_ON_ERROR);

        return (new InMemoryAutoDjHarnessFactory())->create($dump, ScenarioRuntime::fromArray([]));
    }

    private function createWindow(string $start, string $end): DateRange
    {
        return new DateRange(
            CarbonImmutable::parse($start),
            CarbonImmutable::parse($end)
        );
    }

    public function testMemberScheduledOutsideGroupWindowDoesNotPlayViaGroup(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('outside');

        self::assertFalse(
            $harness->scheduler->isPlaylistFullyCoveredByGroupSchedule(
                $playlist,
                $this->createWindow('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testMemberScheduledInsideGroupWindowPlaysViaGroup(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('inside');

        self::assertTrue(
            $harness->scheduler->isPlaylistFullyCoveredByGroupSchedule(
                $playlist,
                $this->createWindow('2018-01-15T12:15:00+00:00', '2018-01-15T12:45:00+00:00')
            )
        );
    }

    public function testStandalonePlaylistNeverPlaysViaGroup(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('standalone');

        self::assertFalse(
            $harness->scheduler->isPlaylistFullyCoveredByGroupSchedule(
                $playlist,
                $this->createWindow('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testMemberOfUnscheduledGroupAlwaysPlaysViaGroup(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('open_member');

        self::assertTrue(
            $harness->scheduler->isPlaylistFullyCoveredByGroupSchedule(
                $playlist,
                $this->createWindow('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testDeeplyNestedMemberOutsideAncestorWindowDoesNotPlayViaGroup(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('deep_member');

        self::assertFalse(
            $harness->scheduler->isPlaylistFullyCoveredByGroupSchedule(
                $playlist,
                $this->createWindow('2018-01-15T03:00:00+00:00', '2018-01-15T04:00:00+00:00')
            )
        );
    }

    public function testMemberIsCoveredAtInstantInsideGroupWindow(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('inside');

        self::assertTrue(
            $harness->scheduler->isPlaylistCoveredByGroupScheduleAt(
                $playlist,
                CarbonImmutable::parse('2018-01-15T12:30:00+00:00')
            )
        );
    }

    public function testMemberIsNotCoveredAtInstantOutsideGroupWindow(): void
    {
        $harness = $this->createHarness();
        $playlist = $harness->entities->playlistForRef('inside');

        self::assertFalse(
            $harness->scheduler->isPlaylistCoveredByGroupScheduleAt(
                $playlist,
                CarbonImmutable::parse('2018-01-15T03:30:00+00:00')
            )
        );
    }
}
