<?php

declare(strict_types=1);

namespace Unit\AutoDJ;

use App\Entity\StationQueue;
use App\Service\PlaylistConfiguration\Schema\PlaylistConfigurationSchema;
use App\Tests\AutoDJ\DumpLoader;
use App\Tests\AutoDJ\InMemoryAutoDjHarness;
use App\Tests\AutoDJ\InMemoryAutoDjHarnessFactory;
use App\Tests\AutoDJ\Scenario\Enums\ExpectQueueMode;
use App\Tests\AutoDJ\Scenario\Enums\ScenarioMode;
use App\Tests\AutoDJ\Scenario\ExpectQueue;
use App\Tests\AutoDJ\Scenario\ScenarioCase;
use Carbon\CarbonImmutable;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

/**
 * @phpstan-import-type PlaylistConfigurationDump from PlaylistConfigurationSchema
 * @phpstan-import-type ProviderRow from DumpLoader
 */
final class QueueBuilderCasesTest extends Unit
{
    /**
     * @return array<string, ProviderRow>
     */
    public static function sequenceCaseProvider(): array
    {
        return array_filter(
            DumpLoader::providerForMode(ScenarioMode::InMemory),
            static fn(array $row): bool => $row['case']->expectSequence !== []
        );
    }

    /**
     * @param PlaylistConfigurationDump $dump
     */
    #[DataProvider('sequenceCaseProvider')]
    public function testSequenceCase(
        array $dump,
        ScenarioCase $case,
        ?string $description = null
    ): void {
        $context = !empty($description) ? "{$description} - " : '';

        $now = CarbonImmutable::parse($case->now);
        CarbonImmutable::setTestNow($now);

        if ($case->seed !== null) {
            mt_srand($case->seed);
        }

        try {
            $autoDjHarness = (new InMemoryAutoDjHarnessFactory())->create($dump, $case->runtime);

            $stepNow = $now;
            $seenMediaPaths = [];
            foreach ($case->expectSequence as $stepIndex => $step) {
                if ($step->now !== null) {
                    $stepNow = CarbonImmutable::parse($step->now);
                    CarbonImmutable::setTestNow($stepNow);
                }

                $autoDjHarness->clearLogs();

                $nextSongs = $autoDjHarness->buildNextSongs(
                    $stepNow,
                    $step->expect->interrupting
                );

                $label = "{$context}step {$stepIndex}";

                $this->assertQueueStep(
                    autoDjHarness: $autoDjHarness,
                    expect: $step->expect,
                    nextSongs: $nextSongs,
                    label: $label
                );

                $seenMediaPaths = $this->assertDistinctWithinCycle(
                    expect: $step->expect,
                    selectedPath: $nextSongs[0]->media->path ?? null,
                    seenMediaPaths: $seenMediaPaths,
                    label: $label
                );
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * @param StationQueue[] $nextSongs
     */
    private function assertQueueStep(
        InMemoryAutoDjHarness $autoDjHarness,
        ExpectQueue $expect,
        array $nextSongs,
        string $label
    ): void {
        $logTrace = "\nAutoDJ log trace:\n{$autoDjHarness->formatLogs()}";

        if ($expect->mode === ExpectQueueMode::None) {
            self::assertEmpty($nextSongs, "[{$label}] Expected no track to be queued.{$logTrace}");
            return;
        }

        self::assertNotEmpty($nextSongs, "[{$label}] Expected a track to be queued.{$logTrace}");

        $first = $nextSongs[0];

        if ($expect->mode === ExpectQueueMode::Exact) {
            if ($expect->playlistRef !== null) {
                self::assertSame(
                    $autoDjHarness->entities->playlistForRef($expect->playlistRef)->name,
                    $first->playlist?->name,
                    "[{$label}] Selected playlist{$logTrace}",
                );
            }

            if ($expect->mediaRef !== null) {
                self::assertSame(
                    $autoDjHarness->entities->mediaByRef[$expect->mediaRef]->path,
                    $first->media?->path,
                    "[{$label}] Selected media{$logTrace}",
                );
            }
        } elseif ($expect->mode === ExpectQueueMode::Membership) {
            $allowedPaths = array_map(
                static fn(string $ref): string => $autoDjHarness->entities->mediaByRef[$ref]->path,
                $expect->mediaAnyOf
            );

            self::assertContains(
                $first->media?->path,
                $allowedPaths,
                "[{$label}] Selected media is one of the allowed set{$logTrace}"
            );
        }
    }

    /**
     * @param string[] $seenMediaPaths
     *
     * @return string[] List of seen media paths
     */
    private function assertDistinctWithinCycle(
        ExpectQueue $expect,
        ?string $selectedPath,
        array $seenMediaPaths,
        string $label
    ): array {
        if (!$expect->distinct || $selectedPath === null) {
            return $seenMediaPaths;
        }

        if (count($seenMediaPaths) >= count($expect->mediaAnyOf)) {
            $seenMediaPaths = [];
        }

        self::assertNotContains(
            $selectedPath,
            $seenMediaPaths,
            "[{$label}] media '{$selectedPath}' repeated within the shuffle cycle"
        );

        $seenMediaPaths[] = $selectedPath;

        return $seenMediaPaths;
    }
}
