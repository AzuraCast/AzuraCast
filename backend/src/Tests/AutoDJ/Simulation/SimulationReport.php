<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ\Simulation;

use League\Csv\Writer;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;

final readonly class SimulationReport
{
    private const array CSV_COLUMNS = [
        'build',
        'played_at',
        'block',
        'measured',
        'playlist_ref',
        'media_ref',
        'artist',
        'title',
        'duration',
        'pick_kind',
        'queue_resets',
        'shares_artist',
        'minutes_since_previous_play',
    ];

    private const int DESCRIPTION_WIDTH = 100;

    /**
     * @param array<string, string> $settings
     * @param list<MetricCheck> $checks
     * @param list<SimulatedPlay> $plays
     */
    public function __construct(
        private string $slug,
        private string $description,
        private array $settings,
        private SimulationMetrics $metrics,
        private array $checks,
        private array $plays
    ) {
    }

    public function writeTo(string $directory): void
    {
        $filesystem = new Filesystem();
        $filesystem->dumpFile("{$directory}/{$this->slug}.summary.txt", $this->summary());
        $filesystem->dumpFile("{$directory}/{$this->slug}.plays.csv", $this->playsCsv());
    }

    public function summary(): string
    {
        $output = new BufferedOutput();
        $io = new SymfonyStyle(new ArrayInput([]), $output);

        $io->title($this->slug);
        $io->text($this->descriptionLines());

        $io->section('Settings');
        $io->horizontalTable(
            array_keys($this->settings),
            [array_values($this->settings)]
        );

        $io->section('Metrics');
        $io->table(
            ['Metric', 'Value', 'Expected', 'Result', 'Scenario key'],
            $this->metricRows()
        );

        $queueResets = $this->metrics->queueResetsInMeasuredBuilds();

        $io->section('Picks (measured plays)');
        $io->table(
            ['Pick kind', 'Meaning', 'Plays'],
            $this->pickRows()
        );

        $io->text([
            "Playlist queue resets in builds with a measured play: {$queueResets}",
            '(A reset happens when none of the remaining queued tracks passes duplicate prevention)',
        ]);

        $repeatCount = count($this->metrics->sortedRepeatIntervalsMinutes);

        $io->section('Time between two plays of the same track (hours)');
        $io->text("Measured plays with an earlier play of the same track: {$repeatCount}");

        $io->newLine();

        $io->table(
            ['Min', 'P10', 'Median', 'P90', 'Max'],
            [$this->repeatIntervalPercentiles()]
        );

        $io->table(
            ['Hours since the previous play (rounded down)', 'Plays'],
            $this->repeatCountRows()
        );

        $io->section('Plays per playlist');
        $io->table(
            ['Playlist', 'Plays', 'Share of measured plays', 'Expected share (from weight)'],
            $this->playlistShareRows()
        );

        $blockCount = $this->metrics->blockCount();

        $io->section('Tracks of the measured playlists by artist');
        $io->table(
            ['Group', 'Tracks', 'Plays', 'Plays per track'],
            $this->artistGroupRows()
        );

        $io->text([
            'A shared artist also appears on another track of the station.',
            'Artist names are split the way duplicate prevention splits them, "A feat. B" counts as A and B.',
        ]);

        $io->newLine();

        $io->table(
            ["Blocks with at least one play (of {$blockCount})", 'Tracks (unique artist)', 'Tracks (shared artist)'],
            $this->blocksPlayedRows()
        );

        return $output->fetch();
    }

    /**
     * @return list<string>
     */
    private function descriptionLines(): array
    {
        $description = OutputFormatter::escape($this->description);

        return explode("\n", wordwrap($description, self::DESCRIPTION_WIDTH));
    }

    /**
     * @return list<list<string>>
     */
    private function metricRows(): array
    {
        return array_map(
            static fn(MetricCheck $check): array => [
                $check->label,
                $check->value,
                $check->expected ?? '-',
                $check->verdict(),
                $check->scenarioKey,
            ],
            $this->checks
        );
    }

    /**
     * @return list<list<int|string>>
     */
    private function pickRows(): array
    {
        $pickRows = [];
        foreach ($this->metrics->playCountByPickKind() as $pickKind => $playCount) {
            $pickRows[] = [
                $pickKind,
                PickKind::from($pickKind)->getDescription(),
                $playCount,
            ];
        }

        return $pickRows;
    }

    /**
     * @return list<string>
     */
    private function repeatIntervalPercentiles(): array
    {
        return array_map(
            fn(float $percentile): string => self::formatHours($this->metrics->repeatIntervalPercentile($percentile)),
            [0.0, 0.1, 0.5, 0.9, 1.0]
        );
    }

    private static function formatHours(?float $minutes): string
    {
        return ($minutes === null) ? '-' : number_format($minutes / 60, 2, '.', '');
    }

    /**
     * @return list<list<int>>
     */
    private function repeatCountRows(): array
    {
        $repeatCountRows = [];
        foreach ($this->metrics->repeatCountByIntervalHours() as $hours => $repeatCount) {
            $repeatCountRows[] = [$hours, $repeatCount];
        }

        return $repeatCountRows;
    }

    /**
     * @return list<list<int|string>>
     */
    private function playlistShareRows(): array
    {
        $measuredPlayCount = $this->metrics->measuredPlayCount();
        $weightShares = $this->metrics->weightSharePerPlaylist();

        $playlistShareRows = [];
        foreach ($this->metrics->playsPerPlaylist() as $playlistRef => $playCount) {
            $playShare = ($measuredPlayCount > 0) ? $playCount / $measuredPlayCount : 0.0;

            $playlistShareRows[] = [
                $playlistRef,
                $playCount,
                self::formatPercent($playShare),
                self::formatPercent($weightShares[$playlistRef]),
            ];
        }

        return $playlistShareRows;
    }

    private static function formatPercent(float $share): string
    {
        $percent = number_format(100 * $share, 1, '.', '');

        return "{$percent}%";
    }

    /**
     * @return list<list<int|string>>
     */
    private function artistGroupRows(): array
    {
        $artistGroupRows = [];
        foreach ($this->metrics->artistGroups() as $group => $stats) {
            $playsPerTrack = ($stats['tracks'] > 0)
                ? number_format($stats['plays'] / $stats['tracks'], 2, '.', '')
                : '-';

            $artistGroupRows[] = [
                "{$group} artist",
                $stats['tracks'],
                $stats['plays'],
                $playsPerTrack,
            ];
        }

        return $artistGroupRows;
    }

    /**
     * @return list<list<int>>
     */
    private function blocksPlayedRows(): array
    {
        $artistGroups = $this->metrics->artistGroups();

        $blocksPlayedRows = [];
        foreach (range(0, $this->metrics->blockCount()) as $blocksPlayed) {
            $blocksPlayedRows[] = [
                $blocksPlayed,
                $artistGroups['unique']['tracks_by_blocks_played'][$blocksPlayed],
                $artistGroups['shared']['tracks_by_blocks_played'][$blocksPlayed],
            ];
        }

        return $blocksPlayedRows;
    }

    private function playsCsv(): string
    {
        $csv = Writer::fromString();
        $csv->setEscape('');

        $csv->insertOne(self::CSV_COLUMNS);
        foreach ($this->plays as $position => $play) {
            $csv->insertOne($this->csvRow($position, $play));
        }

        return $csv->toString();
    }

    /**
     * @return list<int|float|string>
     */
    private function csvRow(int $position, SimulatedPlay $play): array
    {
        $minutesSincePreviousPlay = $this->metrics->minutesSincePreviousPlayByPosition[$position];

        return [
            $play->build,
            $play->playedAt->toIso8601String(),
            $this->metrics->isInMeasuredSpan($play) ? $this->metrics->blockOf($play) : '',
            (int) $this->metrics->isMeasured($play),
            $play->playlistRef ?? '',
            $play->mediaRef ?? '',
            $play->artist,
            $play->title,
            $play->duration,
            $play->pickKind->value,
            $play->queueResets,
            ($play->mediaRef !== null) ? (int) $this->metrics->sharedArtists->sharesArtist($play->mediaRef) : '',
            ($minutesSincePreviousPlay !== null) ? round($minutesSincePreviousPlay, 1) : '',
        ];
    }
}
