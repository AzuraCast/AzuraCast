<?php

declare(strict_types=1);

namespace Unit\AutoDJ;

use App\Service\PlaylistConfiguration\Schema\PlaylistConfigurationSchema;
use App\Tests\AutoDJ\DumpLoader;
use App\Tests\AutoDJ\InMemoryAutoDjHarnessFactory;
use App\Tests\AutoDJ\Scenario\Enums\ScenarioMode;
use App\Tests\AutoDJ\Scenario\ScenarioCase;
use Carbon\CarbonImmutable;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;
use Dom\Text;

/**
 * @phpstan-import-type PlaylistConfigurationDump from PlaylistConfigurationSchema
 * @phpstan-import-type ProviderRow from DumpLoader
 */
final class SchedulerCasesTest extends Unit
{
    /**
     * @return array<string, ProviderRow>
     */
    public static function caseProvider(): array
    {
        return array_filter(
            DumpLoader::providerForMode(ScenarioMode::InMemory),
            static fn(array $row): bool => (
                $row['case']->expectShouldPlay !== []
                || $row['case']->expectSchedulePlay !== []
            )
        );
    }

    /**
     * @param PlaylistConfigurationDump $dump
     */
    #[DataProvider('caseProvider')]
    public function testSchedulerCase(
        array $dump,
        ScenarioCase $case,
        ?string $description = null
    ): void {
        $context = !empty($description) ? "{$description} — " : '';

        $now = CarbonImmutable::parse($case->now);
        CarbonImmutable::setTestNow($now);

        try {
            $harness = (new InMemoryAutoDjHarnessFactory())->create($dump, $case->runtime);

            foreach ($case->expectShouldPlay as $ref => $expected) {
                $playlist = $harness->entities->playlistForRef($ref);

                $harness->clearLogs();
                $actual = $harness->scheduler->shouldPlaylistPlayNow($playlist, $now);

                self::assertSame(
                    $expected,
                    $actual,
                    <<<TEXT
                    {$context}shouldPlaylistPlayNow('{$ref}') at {$now->toIso8601String()}
                    Scheduler log trace:
                    {$harness->formatLogs()}
                    TEXT
                );
            }

            foreach ($case->expectSchedulePlay as $key => $expected) {
                [$ref, $index] = explode('#', $key);

                $playlist = $harness->entities->playlistForRef($ref);

                $schedules = $playlist->schedule_items->toArray();
                $schedule = $schedules[(int) $index];

                $harness->clearLogs();

                $actual = $harness->scheduler->shouldSchedulePlayNow(
                    $schedule,
                    $playlist->station->getTimezoneObject(),
                    $now
                );

                self::assertSame(
                    $expected,
                    $actual,
                    <<<TEXT
                    {$context}shouldSchedulePlayNow('{$key}') at {$now->toIso8601String()}
                    Scheduler log trace:
                    {$harness->formatLogs()}
                    TEXT
                );
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
