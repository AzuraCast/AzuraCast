<?php

declare(strict_types=1);

namespace Unit\AutoDJ;

use App\Service\PlaylistConfiguration\Schema\PlaylistConfigurationSchema;
use App\Tests\AutoDJ\DumpLoader;
use App\Tests\AutoDJ\InMemoryAutoDjHarness;
use App\Tests\AutoDJ\InMemoryAutoDjHarnessFactory;
use App\Tests\AutoDJ\Scenario\Enums\ScenarioMode;
use App\Tests\AutoDJ\Scenario\ScenarioCase;
use App\Tests\AutoDJ\Scenario\ScenarioSimulation;
use App\Tests\AutoDJ\Simulation\MetricCheck;
use App\Tests\AutoDJ\Simulation\PickKind;
use App\Tests\AutoDJ\Simulation\SimulatedPlay;
use App\Tests\AutoDJ\Simulation\SimulationMetrics;
use App\Tests\AutoDJ\Simulation\SimulationReport;
use Carbon\CarbonImmutable;
use Codeception\Attribute\DataProvider;
use Codeception\Test\Unit;

/**
 * @phpstan-import-type PlaylistConfigurationDump from PlaylistConfigurationSchema
 * @phpstan-import-type ProviderRow from DumpLoader
 */
final class QueueBuilderSimulationTest extends Unit
{
    private const int MAX_BUILDS = 20_000;

    private const string QUEUE_RESET_MESSAGE = 'Duplicate prevention yielded no playable song; resetting song queue.';

    /**
     * @return array<string, ProviderRow>
     */
    public static function simulationCaseProvider(): array
    {
        return array_filter(
            DumpLoader::providerForMode(ScenarioMode::InMemory),
            static fn(array $row): bool => $row['case']->simulation !== null
        );
    }

    /**
     * @param PlaylistConfigurationDump $dump
     */
    #[DataProvider('simulationCaseProvider')]
    public function testSimulationCase(
        array $dump,
        ScenarioCase $case,
        ?string $description = null
    ): void {
        /** @var ScenarioSimulation $simulation */
        $simulation = $case->simulation;

        $start = CarbonImmutable::parse($case->now);
        CarbonImmutable::setTestNow($start);

        if ($case->seed !== null) {
            mt_srand($case->seed);
        }

        try {
            $autoDjHarness = (new InMemoryAutoDjHarnessFactory())->create($dump, $case->runtime);
            $this->assertValidSimulation($simulation, $autoDjHarness);

            if ($simulation->backendConfig !== []) {
                $autoDjHarness->entities->station->backend_config = $simulation->backendConfig;
            }

            $measuredFrom = $start->addHours($simulation->historyHours);
            $measuredUntil = $measuredFrom->addHours($simulation->measuredHours);

            [
                'plays' => $plays,
                'builds' => $builds,
                'emptyBuilds' => $emptyBuilds,
            ] = $this->simulate($autoDjHarness, $start, $measuredUntil);

            $backendConfig = $autoDjHarness->entities->station->backend_config;

            $metrics = new SimulationMetrics(
                plays: $plays,
                measurePlaylistRefs: $simulation->measurePlaylistRefs,
                measuredFrom: $measuredFrom,
                measuredHours: $simulation->measuredHours,
                windowMinutes: $backendConfig->duplicate_prevention_time_range,
                entities: $autoDjHarness->entities
            );

            $this->assertValidBlockCount($simulation, $metrics);

            $checks = $metrics->checkAgainst($simulation->expect);

            $blockCount = $metrics->blockCount();
            $blockCountSetting = $metrics->hasPartialLastBlock()
                ? "{$blockCount}, last one partial"
                : (string) $blockCount;

            $report = new SimulationReport(
                slug: $this->reportSlug(),
                description: $description ?? '-',
                settings: [
                    'simulation start' => $start->toIso8601String(),
                    'history before measuring (hours)' => (string) $simulation->historyHours,
                    'measured from' => $measuredFrom->toIso8601String(),
                    'measured until' => $measuredUntil->toIso8601String(),
                    'measured hours' => (string) $simulation->measuredHours,
                    'random seed' => (string) ($case->seed ?? '-'),
                    'duplicate prevention window (minutes)' => (string) $backendConfig->duplicate_prevention_time_range,
                    'artist duplicate prevention window (minutes)' =>
                        (string) $backendConfig->getDuplicatePreventionArtistTimeRange(),
                    'blocks (window-length parts of the measured span)' => $blockCountSetting,
                    'crossfade overlap (seconds)' => (string) $backendConfig->getCrossfadeDuration(),
                    'measured playlists' => implode(', ', $simulation->measurePlaylistRefs),
                    'queue builds' => (string) $builds,
                    'queue builds without a track' => (string) $emptyBuilds,
                ],
                metrics: $metrics,
                checks: $checks,
                plays: $plays
            );

            $report->writeTo(codecept_output_dir('autodj-simulation'));

            $this->assertExpectations($checks, $report->summary());
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    private function assertValidSimulation(ScenarioSimulation $simulation, InMemoryAutoDjHarness $autoDjHarness): void
    {
        self::assertFalse($simulation->expect->isEmpty(), 'A simulation block needs at least one expectation.');
        self::assertNotSame([], $simulation->measurePlaylistRefs, 'A simulation block needs measure_playlists.');
        self::assertGreaterThanOrEqual(
            1,
            $simulation->measuredHours,
            'A simulation block needs measured_hours of at least 1.'
        );

        foreach ($simulation->measurePlaylistRefs as $playlistRef) {
            self::assertArrayHasKey(
                $playlistRef,
                $autoDjHarness->entities->playlistsByRef,
                "measure_playlists contains unknown playlist ref \"{$playlistRef}\"."
            );
        }
    }

    /**
     * @return array{
     *  plays: list<SimulatedPlay>,
     *  builds: int,
     *  emptyBuilds: int
     * }
     */
    private function simulate(
        InMemoryAutoDjHarness $autoDjHarness,
        CarbonImmutable $start,
        CarbonImmutable $end
    ): array {
        $plays = [];
        $builds = 0;
        $emptyBuilds = 0;

        $clock = $start;
        while ($clock < $end) {
            $builds++;
            if ($builds > self::MAX_BUILDS) {
                self::fail(sprintf('Simulation exceeded %d builds before reaching %s.', self::MAX_BUILDS, $end));
            }

            CarbonImmutable::setTestNow($clock);

            $autoDjHarness->markPlayedUntil($clock);
            $autoDjHarness->clearLogs();

            $queueRows = $autoDjHarness->buildNextSongs($clock);

            $logMessages = $autoDjHarness->logMessages();
            $autoDjHarness->clearLogs();

            if ($queueRows === []) {
                $emptyBuilds++;
                $clock = $clock->addMinute();
                continue;
            }

            $pickKind = PickKind::fromLogMessages($logMessages);
            $queueResets = count(array_keys($logMessages, self::QUEUE_RESET_MESSAGE, true));

            foreach ($queueRows as $queueRow) {
                $playlist = $queueRow->playlist;
                $media = $queueRow->media;

                $plays[] = new SimulatedPlay(
                    build: $builds,
                    playedAt: CarbonImmutable::instance($queueRow->timestamp_played ?? $clock),
                    playlistRef: ($playlist !== null) ? $autoDjHarness->entities->refForPlaylist($playlist) : null,
                    mediaRef: ($media !== null) ? $autoDjHarness->entities->refForMedia($media) : null,
                    artist: $queueRow->artist ?? '',
                    title: $queueRow->title ?? '',
                    duration: $queueRow->duration ?? 0.0,
                    pickKind: $pickKind,
                    queueResets: $queueResets
                );
            }

            $clock = $autoDjHarness->expectedPlayTimeAfter($queueRows[array_key_last($queueRows)]);
        }

        return [
            'plays' => $plays,
            'builds' => $builds,
            'emptyBuilds' => $emptyBuilds,
        ];
    }

    private function assertValidBlockCount(ScenarioSimulation $simulation, SimulationMetrics $metrics): void
    {
        if ($simulation->expect->maxRepeatedPairShare !== null) {
            self::assertGreaterThanOrEqual(
                2,
                $metrics->blockCount(),
                'max_repeated_pair_share needs at least 2 blocks of the duplicate prevention window.'
            );
        }
    }

    /**
     * The provider key without the scenario description, reduced to a file name safe string
     */
    private function reportSlug(): string
    {
        $label = explode(' — ', (string) $this->dataName(), 2)[0];

        return trim((string) preg_replace('/[^A-Za-z0-9._-]+/', '-', $label), '-');
    }

    /**
     * @param list<MetricCheck> $checks
     */
    private function assertExpectations(array $checks, string $summary): void
    {
        foreach ($checks as $check) {
            if ($check->isExpected()) {
                self::assertTrue($check->passes, "{$check}\n\n{$summary}");
            }
        }
    }
}
