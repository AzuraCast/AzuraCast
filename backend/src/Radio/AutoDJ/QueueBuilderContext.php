<?php

declare(strict_types=1);

namespace App\Radio\AutoDJ;

use App\Entity\Station;
use App\Entity\StationPlaylist;
use App\Entity\StationRequest;
use App\Entity\StationSchedule;
use App\Utilities\DateRange;
use Carbon\CarbonInterface;
use generator;
use Monolog\Logger;

/**
 * Handles registration, tracking, and selection of which playlist to play.
 * Instances of this class are single-use.
 */
final class QueueBuilderContext
{
    /**
     * Maps ScheduleContexts to their playlists' priorities as they're being registered.
     */
    private array $registeredSchedulerContexts;
    private int $registeredPlaylistCount = 0;
    public function __construct(
        public readonly Station $station,
        private readonly QueueBuilder $queueBuilder,
        private readonly Scheduler $scheduler,
        private readonly Logger $logger,
        public readonly CarbonInterface $expectedPlayTime
    ) {
    }

    /**
     * Returns a count of all successfully registered playlists.
     */
    public function getPlaylistCount(): int
    {
        return $this->registeredPlaylistCount;
    }
    /**
     * Qualifies and registers a playlist for selection.
     */
    public function registerPlaylist(StationPlaylist $playlist): bool
    {
        if (!$playlist->getIsEnabled()) {
            $this->logger->debug(
                sprintf(
                    'Playlist "%s" is disabled.',
                    $playlist->getName()
                )
            );
            return false;
        }

        if (0 === count($playlist->getMediaItems())) {
            $this->logger->debug(
                sprintf(
                    'Playlist "%s" is empty.',
                    $playlist->getName()
                )
            );
            return false;
        }
        $ctx = new SchedulerContext($playlist, $this->expectedPlayTime);
        if (!$this->scheduler->shouldPlaylistPlayNow($ctx)) {
            return false;
        }

        $playlistId = $playlist->getId();
        $priority = $this->queueBuilder->getPlaylistPriority($playlist);
        $this->registeredSchedulerContexts[$priority][$playlistId] = $ctx;
        $this->registeredPlaylistCount++;
        return true;
    }
    /**
     * Generate a map of priority values to playlist names for logging purposes.
     */
    public function getLogPriorities(): array
    {
        $summary = [];
        foreach ($this->registeredSchedulerContexts as $priority => $group) {
            foreach ($group as $ctx) {
                $summary[$priority][] = $ctx->playlist->getName();
            }
        }
        return $summary;
    }
    /**
     * Contextual iteration over all registered playlists, organized by priority and weight.
     */
    public function getPlaylists(): Generator
    {
                // Build our list of playlists, sorted first by priority, then by weight.
        krsort($this->registeredSchedulerContexts);
        foreach ($this->registeredSchedulerContexts as $contexts) {
            $weights = [];
            foreach ($contexts as $ctx) {
                $playlist = $ctx->playlist;
                $weights[$playlist->getId()] = $playlist->getWeight();
            }
            $weights = $this->weightedShuffle($weights);
            foreach ($weights as $id => $weight) {
                yield $contexts[$id];
            }
        }
    }
    /**
     * Apply a weighted shuffle to the given array in the form:
     *  [ key1 => weight1, key2 => weight2 ]
     *
     * Based on: https://gist.github.com/savvot/e684551953a1716208fbda6c4bb2f344
     *
     * @param array& $original
     * @return array
     */
    private function weightedShuffle(array &$original): array
    {
        $new = $original;
        $max = 1.0 / mt_getrandmax();

        array_walk(
            $new,
            static function (&$value) use ($max): void {
                $value = (mt_rand() * $max) ** (1.0 / $value);
            }
        );

        arsort($new);

        array_walk(
            $new,
            static function (&$value, $key) use ($original): void {
                $value = $original[$key];
            }
        );

        return $new;
    }
}
