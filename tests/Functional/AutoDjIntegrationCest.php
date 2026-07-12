<?php

declare(strict_types=1);

namespace Functional;

use App\Entity\Song;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Entity\StationPlaylist;
use App\Entity\StationPlaylistGroup;
use App\Entity\StationPlaylistMedia;
use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Radio\AutoDJ\QueueBuilder;
use App\Radio\AutoDJ\Scheduler;
use App\Service\PlaylistConfiguration\ImportSummary;
use App\Service\PlaylistConfiguration\PlaylistConfigurationImporter;
use App\Service\PlaylistConfiguration\Schema\PlaylistConfigurationSchema;
use App\Tests\AutoDJ\DumpLoader;
use App\Tests\AutoDJ\Scenario\Enums\ExpectQueueMode;
use App\Tests\AutoDJ\Scenario\Enums\ScenarioMode;
use App\Tests\AutoDJ\Scenario\ExpectQueue;
use App\Tests\AutoDJ\Scenario\QueueHistoryEntry;
use App\Tests\AutoDJ\Scenario\ScenarioCase;
use App\Tests\AutoDJ\Scenario\ScenarioRuntime;
use Carbon\CarbonImmutable;
use Codeception\Attribute\DataProvider;
use Codeception\Example;
use DateTimeImmutable;
use FunctionalTester;

/**
 * @phpstan-import-type PlaylistConfigurationDump from PlaylistConfigurationSchema
 * @phpstan-import-type ProviderRow from DumpLoader
 */
final class AutoDjIntegrationCest extends CestAbstract
{
    /**
     * @return array<string, ProviderRow>
     */
    public function schedulerCasesProvider(): array
    {
        return array_filter(
            DumpLoader::providerForMode(ScenarioMode::Integration),
            static fn(array $row): bool => (
                $row['case']->expectShouldPlay !== []
                || $row['case']->expectSchedulePlay !== []
            )
        );
    }

    #[DataProvider('schedulerCasesProvider')]
    public function schedulerCase(FunctionalTester $I, Example $example): void
    {
        /** @var ScenarioCase $case */
        $case = $example['case'];

        /** @var PlaylistConfigurationDump $dump */
        $dump = $example['dump'];

        $I->wantTo('Verify AutoDJ scheduling against the database');

        $this->setupComplete($I);

        $summary = $this->importDump($dump);

        $this->applyRuntime($summary, $case->runtime);

        ['playlistsByRef' => $playlistsByRef] = $this->refreshEntitiesAfterImport($summary);

        $now = CarbonImmutable::parse($case->now);
        CarbonImmutable::setTestNow($now);

        try {
            $scheduler = $this->di->get(Scheduler::class);

            foreach ($case->expectShouldPlay as $ref => $expected) {
                $I->assertSame(
                    $expected,
                    $scheduler->shouldPlaylistPlayNow($playlistsByRef[$ref], $now),
                    "shouldPlaylistPlayNow('{$ref}')"
                );
            }

            foreach ($case->expectSchedulePlay as $key => $expected) {
                [$ref, $index] = explode('#', $key);

                $playlist = $playlistsByRef[$ref];
                $schedule = $playlist->schedule_items->toArray()[(int)$index];

                $I->assertSame(
                    $expected,
                    $scheduler->shouldSchedulePlayNow($schedule, $playlist->station->getTimezoneObject(), $now),
                    "shouldSchedulePlayNow('{$key}')"
                );
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * @return array<string, ProviderRow>
     */
    protected function sequenceCasesProvider(): array
    {
        return array_filter(
            DumpLoader::providerForMode(ScenarioMode::Integration),
            static fn(array $row): bool => $row['case']->expectSequence !== []
        );
    }

    #[DataProvider('sequenceCasesProvider')]
    public function sequenceCase(FunctionalTester $I, Example $example): void
    {
        /** @var ScenarioCase $case */
        $case = $example['case'];

        /** @var PlaylistConfigurationDump $dump */
        $dump = $example['dump'];

        $I->wantTo('Verify AutoDJ queue sequence against the database');

        $this->setupComplete($I);

        $summary = $this->importDump($dump);

        $this->applyRuntime($summary, $case->runtime);

        $playlistIdsByRef = array_map(
            static fn(StationPlaylist $playlist): int => $playlist->id,
            $summary->playlistsByRef
        );

        $mediaIdsByRef = array_map(
            static fn(StationMedia $media): int => $media->id,
            $summary->mediaByRef
        );

        [
            'station' => $station,
            'playlistsByRef' => $playlistsByRef,
            'mediaByRef' => $mediaByRef,
        ] = $this->refetchByIds($playlistIdsByRef, $mediaIdsByRef);

        $now = CarbonImmutable::parse($case->now);
        CarbonImmutable::setTestNow($now);

        if ($case->seed !== null) {
            mt_srand($case->seed);
        }

        try {
            $stepNow = $now;
            $seenMediaPaths = [];
            foreach ($case->expectSequence as $stepIndex => $step) {
                if ($step->now !== null) {
                    $stepNow = CarbonImmutable::parse($step->now);
                    CarbonImmutable::setTestNow($stepNow);
                }

                $result = $this->buildNext(
                    $station,
                    $stepNow,
                    $step->expect->interrupting
                );

                $label = "step {$stepIndex}";

                $this->assertQueueStep(
                    I: $I,
                    expect: $step->expect,
                    result: $result,
                    playlistsByRef: $playlistsByRef,
                    mediaByRef: $mediaByRef,
                    label: $label
                );

                $seenMediaPaths = $this->assertDistinctWithinCycle(
                    I: $I,
                    expect: $step->expect,
                    selectedPath: $result[0]->media->path ?? null,
                    seenMediaPaths: $seenMediaPaths,
                    label: $label
                );

                // Commit played state so next build advances
                $this->em->flush();

                [
                    'station' => $station,
                    'playlistsByRef' => $playlistsByRef,
                    'mediaByRef' => $mediaByRef,
                ] = $this->refetchByIds($playlistIdsByRef, $mediaIdsByRef);
            }
        } finally {
            CarbonImmutable::setTestNow();
        }
    }

    /**
     * @param StationQueue[] $result
     * @param array<string, StationPlaylist> $playlistsByRef
     * @param array<string, StationMedia> $mediaByRef
     */
    private function assertQueueStep(
        FunctionalTester $I,
        ExpectQueue $expect,
        array $result,
        array $playlistsByRef,
        array $mediaByRef,
        string $label
    ): void {
        if ($expect->mode === ExpectQueueMode::None) {
            $I->assertEmpty($result, "[{$label}] Expected no track to be queued.");
            return;
        }

        $I->assertNotEmpty($result, "[{$label}] Expected a track to be queued.");
        $first = $result[0];

        if ($expect->mode === ExpectQueueMode::Exact) {
            if ($expect->playlistRef !== null) {
                $I->assertSame(
                    $playlistsByRef[$expect->playlistRef]->name,
                    $first->playlist?->name,
                    "[{$label}] Selected playlist"
                );
            }

            if ($expect->mediaRef !== null) {
                $I->assertSame(
                    $mediaByRef[$expect->mediaRef]->path,
                    $first->media?->path,
                    "[{$label}] Selected media"
                );
            }
        } elseif ($expect->mode === ExpectQueueMode::Membership) {
            $allowed = array_map(
                static fn(string $ref): string => $mediaByRef[$ref]->path,
                $expect->mediaAnyOf
            );

            $I->assertContains(
                $first->media?->path,
                $allowed,
                "[{$label}] Selected media is one of the allowed set"
            );
        }
    }

    /**
     * @param string[] $seenMediaPaths
     *
     * @return string[] List of seen media paths
     */
    private function assertDistinctWithinCycle(
        FunctionalTester $I,
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

        $I->assertNotContains(
            $selectedPath,
            $seenMediaPaths,
            "[{$label}] media '{$selectedPath}' repeated within the shuffle cycle"
        );

        $seenMediaPaths[] = $selectedPath;

        return $seenMediaPaths;
    }

    /**
     * @param PlaylistConfigurationDump $dump
     */
    private function importDump(array $dump): ImportSummary
    {
        $importer = $this->di->get(PlaylistConfigurationImporter::class);

        $summary = $importer->import($dump, $this->getTestStation());

        $this->em->flush();

        return $summary;
    }

    private function applyRuntime(ImportSummary $summary, ScenarioRuntime $runtime): void
    {
        foreach ($runtime->playlists as $ref => $state) {
            if (!isset($summary->playlistsByRef[$ref])) {
                continue;
            }

            $playlist = $summary->playlistsByRef[$ref];
            if ($state->hasPlayedAt) {
                $playlist->played_at = $state->playedAt;
            }

            if ($state->hasQueueResetAt) {
                $playlist->queue_reset_at = $state->queueResetAt;
            }

            $this->em->persist($playlist);
        }

        foreach ($runtime->playlistMedia as $refPair => $state) {
            [$playlistRef, $mediaRef] = explode(':', $refPair);
            if (!isset($summary->playlistsByRef[$playlistRef], $summary->mediaByRef[$mediaRef])) {
                continue;
            }

            $spm = $this->em->getRepository(StationPlaylistMedia::class)->findOneBy([
                'playlist' => $summary->playlistsByRef[$playlistRef],
                'media' => $summary->mediaByRef[$mediaRef],
            ]);

            if ($spm === null) {
                continue;
            }

            if ($state->isQueued !== null) {
                $spm->is_queued = $state->isQueued;
            }

            if ($state->lastPlayed !== null) {
                $spm->last_played = $state->lastPlayed;
            }

            $this->em->persist($spm);
        }

        foreach ($runtime->groupMembers as $refPair => $state) {
            [$containerRef, $memberRef] = explode(':', $refPair);
            if (!isset($summary->playlistsByRef[$containerRef], $summary->playlistsByRef[$memberRef])) {
                continue;
            }

            $spg = $this->em->getRepository(StationPlaylistGroup::class)->findOneBy([
                'playlist_group' => $summary->playlistsByRef[$containerRef],
                'playlist' => $summary->playlistsByRef[$memberRef],
            ]);

            if ($spg === null) {
                continue;
            }

            if ($state->isQueued !== null) {
                $spg->is_queued = $state->isQueued;
            }

            if ($state->consecutivePlaysCount !== null) {
                $spg->consecutive_plays_count = $state->consecutivePlaysCount;
            }

            if ($state->lastPlayed !== null) {
                $spg->last_played = $state->lastPlayed;
            }

            $this->em->persist($spg);
        }

        $this->em->flush();

        $this->seedQueueHistory($summary, $runtime);
    }

    private function seedQueueHistory(ImportSummary $summary, ScenarioRuntime $runtime): void
    {
        if ($runtime->queueHistory === []) {
            return;
        }

        $station = $this->getTestStation();

        $entries = $runtime->queueHistory;
        usort(
            $entries,
            static fn(QueueHistoryEntry $a, QueueHistoryEntry $b): int
                => $a->timestampPlayed <=> $b->timestampPlayed
        );

        foreach ($entries as $entry) {
            $media = $summary->mediaByRef[$entry->mediaRef ?? ''] ?? null;

            $stationQueueEntry = ($media !== null)
                ? StationQueue::fromMedia($station, $media)
                : new StationQueue(
                    $station,
                    Song::createFromArray([
                        'text' => $entry->songId ?? '',
                        'title' => $entry->title,
                        'artist' => $entry->artist,
                    ])
                );

            if (isset($summary->playlistsByRef[$entry->playlistRef ?? ''])) {
                $stationQueueEntry->playlist = $summary->playlistsByRef[$entry->playlistRef ?? ''];
            }

            $stationQueueEntry->is_played = true;
            $stationQueueEntry->timestamp_played = new DateTimeImmutable("@{$entry->timestampPlayed}");
            $stationQueueEntry->is_visible = $entry->isVisible;

            $this->em->persist($stationQueueEntry);
        }

        $this->em->flush();
    }

    /**
     * @return array{
     *     station: Station,
     *     playlistsByRef: array<string, StationPlaylist>,
     *     mediaByRef: array<string, StationMedia>
     * }
     */
    private function refreshEntitiesAfterImport(ImportSummary $summary): array
    {
        $playlistIds = array_map(
            static fn(StationPlaylist $playlist): int => $playlist->id,
            $summary->playlistsByRef
        );

        $mediaIds = array_map(
            static fn(StationMedia $media): int => $media->id,
            $summary->mediaByRef
        );

        return $this->refetchByIds($playlistIds, $mediaIds);
    }

    /**
     * @param array<string, int> $playlistIdsByRef
     * @param array<string, int> $mediaIdsByRef
     *
     * @return array{
     *     station: Station,
     *     playlistsByRef: array<string, StationPlaylist>,
     *     mediaByRef: array<string, StationMedia>
     * }
     */
    private function refetchByIds(array $playlistIdsByRef, array $mediaIdsByRef): array
    {
        $this->em->clear();

        $station = $this->getTestStation();

        $playlistsByRef = [];
        foreach ($playlistIdsByRef as $ref => $id) {
            $playlist = $this->em->find(StationPlaylist::class, $id);

            if ($playlist !== null) {
                $playlistsByRef[$ref] = $playlist;
            }
        }

        $mediaByRef = [];
        foreach ($mediaIdsByRef as $ref => $id) {
            $media = $this->em->find(StationMedia::class, $id);

            if ($media !== null) {
                $mediaByRef[$ref] = $media;
            }
        }

        return [
            'station' => $station,
            'playlistsByRef' => $playlistsByRef,
            'mediaByRef' => $mediaByRef,
        ];
    }

    /**
     * @return StationQueue[]
     */
    private function buildNext(Station $station, DateTimeImmutable $now, bool $interrupting): array
    {
        $queueBuilder = $this->di->get(QueueBuilder::class);

        $event = new BuildQueue(
            station: $station,
            expectedCueTime: $now,
            expectedPlayTime: $now,
            isInterrupting: $interrupting
        );

        $queueBuilder->getNextSongFromRequests($event);
        if ($event->getNextSongs() === []) {
            $queueBuilder->calculateNextSong($event);
        }

        return $event->getNextSongs();
    }
}
