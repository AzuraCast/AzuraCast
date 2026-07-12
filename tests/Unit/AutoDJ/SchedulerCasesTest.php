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
                self::assertSame(
                    $expected,
                    $harness->scheduler->shouldPlaylistPlayNow($playlist, $now),
                    "{$context}shouldPlaylistPlayNow('{$ref}') at {$now->toIso8601String()}"
                );
            }

            foreach ($case->expectSchedulePlay as $key => $expected) {
                [$ref, $index] = explode('#', $key);

                $playlist = $harness->entities->playlistForRef($ref);

                $schedules = $playlist->schedule_items->toArray();
                $schedule = $schedules[(int) $index];

                self::assertSame(
                    $expected,
                    $harness->scheduler->shouldSchedulePlayNow(
                        $schedule,
                        $playlist->station->getTimezoneObject(),
                        $now
                    ),
                    "{$context}shouldSchedulePlayNow('{$key}') at {$now->toIso8601String()}",
                );
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }
}
