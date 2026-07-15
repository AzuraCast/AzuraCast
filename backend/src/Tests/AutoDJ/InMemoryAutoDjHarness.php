<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Radio\AutoDJ\QueueBuilder;
use App\Radio\AutoDJ\Scheduler;
use DateTimeImmutable;

final readonly class InMemoryAutoDjHarness
{
    public function __construct(
        public InMemoryEntityStore $entities,
        public Scheduler $scheduler,
        private QueueBuilder $queueBuilder,
        private InMemoryAutoDjDataProxy $dataProxy
    ) {
    }

    /**
     * @return StationQueue[]
     */
    public function buildNextSongs(DateTimeImmutable $now, bool $interrupting = false): array
    {
        $event = new BuildQueue(
            station: $this->entities->station,
            expectedCueTime: $now,
            expectedPlayTime: $now,
            lastPlayedSongId: null,
            isInterrupting: $interrupting
        );

        $this->queueBuilder->getNextSongFromRequests($event);
        if ($event->getNextSongs() === []) {
            $this->queueBuilder->calculateNextSong($event);
        }

        $nextSongs = $event->getNextSongs();

        foreach ($nextSongs as $stationQueueEntry) {
            $this->dataProxy->recordBuiltEntry($stationQueueEntry);
        }

        return $nextSongs;
    }
}
