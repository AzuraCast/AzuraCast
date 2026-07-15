<?php

declare(strict_types=1);

namespace App\Tests\AutoDJ;

use App\Entity\StationQueue;
use App\Event\Radio\BuildQueue;
use App\Radio\AutoDJ\QueueBuilder;
use App\Radio\AutoDJ\Scheduler;
use DateTimeImmutable;
use Monolog\Handler\TestHandler;

use const JSON_UNESCAPED_SLASHES;

final readonly class InMemoryAutoDjHarness
{
    public function __construct(
        public InMemoryEntityStore $entities,
        public Scheduler $scheduler,
        private QueueBuilder $queueBuilder,
        private InMemoryAutoDjDataProxy $dataProxy,
        private TestHandler $logHandler
    ) {
    }

    public function clearLogs(): void
    {
        $this->logHandler->clear();
    }

    public function formatLogs(): string
    {
        $lines = [];
        foreach ($this->logHandler->getRecords() as $record) {
            $line = sprintf('[%s] %s', $record->level->getName(), $record->message);

            if ($record->context !== []) {
                $line .= ' ' . json_encode($record->context, JSON_UNESCAPED_SLASHES);
            }

            $lines[] = $line;
        }

        return ($lines === [])
            ? '(no log output captured)'
            : implode("\n", $lines);
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
