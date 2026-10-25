<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Simulation;

use App\Tests\AutoDJ\InMemoryEntityStore;
use App\Tests\AutoDJ\Scenario\SimulationExpectations;
use Carbon\CarbonImmutable;

/**
 * Statistics over a simulated run, only measured plays counted:
 * - Plays from the measured playlists between measuredFrom and measuredUntil
 * - Blocks of the duplicate prevention window from measuredFrom
 *
 * @phpstan-type ArtistGroupStats array{
 *  tracks: int,
 *  plays: int,
 *  tracks_by_blocks_played: array<int, int>
 * }
 */
final class SimulationMetrics
{
    public const int NEAR_WINDOW_TOLERANCE_MINUTES = 60;

    public readonly CarbonImmutable $measuredUntil;

    public readonly SharedArtists $sharedArtists;

    /** @var array<int, ?float> */
    public readonly array $minutesSincePreviousPlayByPosition;

    /** @var list<SimulatedPlay> */
    public private(set) array $measuredPlays = [];

    /** @var list<float> */
    public private(set) array $sortedRepeatIntervalsMinutes = [];

    /**
     * @param list<SimulatedPlay> $plays
     * @param string[] $measurePlaylistRefs
     */
    public function __construct(
        array $plays,
        public readonly array $measurePlaylistRefs,
        public readonly CarbonImmutable $measuredFrom,
        public readonly int $measuredHours,
        public readonly int $windowMinutes,
        private readonly InMemoryEntityStore $entities
    ) {
        $this->measuredUntil = $measuredFrom->addHours($measuredHours);
        $this->sharedArtists = new SharedArtists($entities);
        $this->minutesSincePreviousPlayByPosition = self::minutesSincePreviousPlayByPosition($plays);

        foreach ($plays as $position => $play) {
            if (!$this->isMeasured($play)) {
                continue;
            }

            $this->measuredPlays[] = $play;

            $minutesSincePreviousPlay = $this->minutesSincePreviousPlayByPosition[$position];
            if ($minutesSincePreviousPlay !== null) {
                $this->sortedRepeatIntervalsMinutes[] = $minutesSincePreviousPlay;
            }
        }

        sort($this->sortedRepeatIntervalsMinutes);
    }

    public function isMeasured(SimulatedPlay $play): bool
    {
        return $play->mediaRef !== null
            && $this->isInMeasuredSpan($play)
            && in_array($play->playlistRef, $this->measurePlaylistRefs, true);
    }

    public function isInMeasuredSpan(SimulatedPlay $play): bool
    {
        return $play->playedAt >= $this->measuredFrom
            && $play->playedAt < $this->measuredUntil;
    }

    /**
     * @return list<MetricCheck>
     */
    public function checkAgainst(SimulationExpectations $expect): array
    {
        $tolerance = self::NEAR_WINDOW_TOLERANCE_MINUTES;

        return [
            MetricCheck::exactly(
                label: 'Measured plays',
                scenarioKey: 'measured_play_count',
                value: $this->measuredPlayCount(),
                expected: $expect->measuredPlayCount
            ),
            MetricCheck::atLeast(
                label: 'Shortest time between two plays of a track (minutes)',
                scenarioKey: 'min_repeat_interval_minutes',
                value: $this->minRepeatIntervalMinutes(),
                minimum: $expect->minRepeatIntervalMinutes
            ),
            MetricCheck::atMost(
                label: "Share of repeats within {$tolerance} minutes of the window length",
                scenarioKey: 'max_repeat_share_near_window',
                value: $this->repeatShareNearWindow(),
                maximum: $expect->maxRepeatShareNearWindow
            ),
            MetricCheck::atMost(
                label: 'Share of back-to-back pairs that were also back to back in the previous block',
                scenarioKey: 'max_repeated_pair_share',
                value: $this->repeatedPairShare(),
                maximum: $expect->maxRepeatedPairShare
            ),
            MetricCheck::atMost(
                label: 'Share of tracks without a measured play',
                scenarioKey: 'max_never_played_share',
                value: $this->neverPlayedShare(),
                maximum: $expect->maxNeverPlayedShare
            ),
        ];
    }

    public function measuredPlayCount(): int
    {
        return count($this->measuredPlays);
    }

    public function minRepeatIntervalMinutes(): ?float
    {
        return $this->sortedRepeatIntervalsMinutes[0] ?? null;
    }

    public function repeatShareNearWindow(): ?float
    {
        if ($this->sortedRepeatIntervalsMinutes === []) {
            return null;
        }

        $repeatsNearWindow = array_filter(
            $this->sortedRepeatIntervalsMinutes,
            fn(float $interval): bool => abs($interval - $this->windowMinutes) <= self::NEAR_WINDOW_TOLERANCE_MINUTES
        );

        return count($repeatsNearWindow) / count($this->sortedRepeatIntervalsMinutes);
    }

    public function repeatedPairShare(): ?float
    {
        $pairsByBlock = $this->consecutivePairsByBlock();

        $comparedPairs = 0;
        $repeatedPairs = 0;
        for ($block = 1; $block < $this->blockCount(); $block++) {
            $previousBlockPairs = array_flip($pairsByBlock[$block - 1] ?? []);

            foreach ($pairsByBlock[$block] ?? [] as $pair) {
                $comparedPairs++;

                if (isset($previousBlockPairs[$pair])) {
                    $repeatedPairs++;
                }
            }
        }

        return ($comparedPairs > 0) ? $repeatedPairs / $comparedPairs : null;
    }

    public function neverPlayedShare(): float
    {
        $playCountByMeasuredTrack = $this->playCountByMeasuredTrack();
        if ($playCountByMeasuredTrack === []) {
            return 0.0;
        }

        $neverPlayedTracks = array_filter(
            $playCountByMeasuredTrack,
            static fn(int $playCount): bool => $playCount === 0
        );

        return count($neverPlayedTracks) / count($playCountByMeasuredTrack);
    }

    /**
     * @return array<string, int>
     */
    public function playCountByPickKind(): array
    {
        $playCountByPickKind = array_fill_keys(
            array_map(static fn(PickKind $pickKind): string => $pickKind->value, PickKind::cases()),
            0
        );

        foreach ($this->measuredPlays as $play) {
            $playCountByPickKind[$play->pickKind->value]++;
        }

        return $playCountByPickKind;
    }

    public function queueResetsInMeasuredBuilds(): int
    {
        $queueResetsByBuild = [];
        foreach ($this->measuredPlays as $play) {
            $queueResetsByBuild[$play->build] = $play->queueResets;
        }

        return array_sum($queueResetsByBuild);
    }

    public function repeatIntervalPercentile(float $percentile): ?float
    {
        $count = count($this->sortedRepeatIntervalsMinutes);
        if ($count === 0) {
            return null;
        }

        $rank = max(1, (int) ceil($percentile * $count));

        return $this->sortedRepeatIntervalsMinutes[min($rank, $count) - 1];
    }

    /**
     * @return array<int, int>
     */
    public function repeatCountByIntervalHours(): array
    {
        $repeatCountByIntervalHours = [];
        foreach ($this->sortedRepeatIntervalsMinutes as $interval) {
            $hours = (int) floor($interval / 60);
            $repeatCountByIntervalHours[$hours] = ($repeatCountByIntervalHours[$hours] ?? 0) + 1;
        }

        ksort($repeatCountByIntervalHours);

        return $repeatCountByIntervalHours;
    }

    /**
     * @return array<string, int>
     */
    public function playsPerPlaylist(): array
    {
        $playsPerPlaylist = array_fill_keys($this->measurePlaylistRefs, 0);
        foreach ($this->measuredPlays as $play) {
            $playsPerPlaylist[(string) $play->playlistRef]++;
        }

        return $playsPerPlaylist;
    }

    /**
     * @return array<string, float>
     */
    public function weightSharePerPlaylist(): array
    {
        $weights = [];
        foreach ($this->measurePlaylistRefs as $playlistRef) {
            $weights[$playlistRef] = $this->entities->playlistForRef($playlistRef)->weight;
        }

        $totalWeight = array_sum($weights);

        return array_map(
            static fn(int $weight): float => ($totalWeight > 0) ? $weight / $totalWeight : 0.0,
            $weights
        );
    }

    /**
     * @return array{
     *  unique: ArtistGroupStats,
     *  shared: ArtistGroupStats
     * }
     */
    public function artistGroups(): array
    {
        $emptyGroup = [
            'tracks' => 0,
            'plays' => 0,
            'tracks_by_blocks_played' => array_fill(0, $this->blockCount() + 1, 0),
        ];

        $artistGroups = [
            'unique' => $emptyGroup,
            'shared' => $emptyGroup,
        ];

        $blocksPlayedByMeasuredTrack = $this->blocksPlayedByMeasuredTrack();
        foreach ($this->playCountByMeasuredTrack() as $mediaRef => $playCount) {
            $group = $this->sharedArtists->sharesArtist((string) $mediaRef) ? 'shared' : 'unique';

            $artistGroups[$group]['tracks']++;
            $artistGroups[$group]['plays'] += $playCount;
            $artistGroups[$group]['tracks_by_blocks_played'][$blocksPlayedByMeasuredTrack[$mediaRef]]++;
        }

        return $artistGroups;
    }

    public function blockCount(): int
    {
        return (int) ceil($this->measuredHours * 60 / $this->windowMinutes);
    }

    public function hasPartialLastBlock(): bool
    {
        return ($this->measuredHours * 60) % $this->windowMinutes !== 0;
    }

    public function blockOf(SimulatedPlay $play): int
    {
        return (int) floor($this->measuredFrom->diffInMinutes($play->playedAt) / $this->windowMinutes);
    }

    /**
     * @param list<SimulatedPlay> $plays
     *
     * @return array<int, ?float>
     */
    private static function minutesSincePreviousPlayByPosition(array $plays): array
    {
        $lastPlayedAtByTrack = [];
        $minutesSincePreviousPlayByPosition = [];

        foreach ($plays as $position => $play) {
            if ($play->mediaRef === null) {
                $minutesSincePreviousPlayByPosition[$position] = null;
                continue;
            }

            $previousPlayedAt = $lastPlayedAtByTrack[$play->mediaRef] ?? null;
            $minutesSincePreviousPlayByPosition[$position] = $previousPlayedAt?->diffInMinutes($play->playedAt);

            $lastPlayedAtByTrack[$play->mediaRef] = $play->playedAt;
        }

        return $minutesSincePreviousPlayByPosition;
    }

    /**
     * @return array<int, list<string>>
     */
    private function consecutivePairsByBlock(): array
    {
        $pairsByBlock = [];

        $previousPlay = null;
        foreach ($this->measuredPlays as $play) {
            $block = $this->blockOf($play);

            if ($previousPlay !== null && $this->blockOf($previousPlay) === $block) {
                $pairsByBlock[$block][] = "{$previousPlay->mediaRef}>{$play->mediaRef}";
            }

            $previousPlay = $play;
        }

        return $pairsByBlock;
    }

    /**
     * @return array<string, int>
     */
    private function playCountByMeasuredTrack(): array
    {
        $playCountByMeasuredTrack = array_fill_keys($this->measuredTrackRefs(), 0);
        foreach ($this->measuredPlays as $play) {
            $playCountByMeasuredTrack[(string) $play->mediaRef]++;
        }

        return $playCountByMeasuredTrack;
    }

    /**
     * @return array<string, int>
     */
    private function blocksPlayedByMeasuredTrack(): array
    {
        $playBlocksByMeasuredTrack = array_fill_keys($this->measuredTrackRefs(), []);
        foreach ($this->measuredPlays as $play) {
            $playBlocksByMeasuredTrack[(string) $play->mediaRef][$this->blockOf($play)] = true;
        }

        return array_map(count(...), $playBlocksByMeasuredTrack);
    }

    /**
     * @return list<string>
     */
    private function measuredTrackRefs(): array
    {
        $trackRefs = [];
        foreach ($this->measurePlaylistRefs as $playlistRef) {
            foreach ($this->entities->playlistForRef($playlistRef)->media_items as $spm) {
                $mediaRef = $this->entities->refForMedia($spm->media);
                if ($mediaRef !== null) {
                    $trackRefs[$mediaRef] = $mediaRef;
                }
            }
        }

        return array_values($trackRefs);
    }
}
