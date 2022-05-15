<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Event\Radio\AnnotateNextSong;
use App\Radio\Adapters;
use App\Radio\AutoDJ\Queue;
use App\Radio\Backend\Liquidsoap;
use App\Radio\Enums\LiquidsoapQueues;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;

class QueueInterruptingTracks extends AbstractTask
{
    public function __construct(
        protected Queue $queue,
        protected Adapters $adapters,
        protected EventDispatcherInterface $eventDispatcher,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        parent::__construct($em, $logger);
    }

    public static function getSchedulePattern(): string
    {
        return self::SCHEDULE_EVERY_MINUTE;
    }

    /**
     * Manually process any requests for stations that use "Manual AutoDJ" mode.
     *
     * @param bool $force
     */
    public function run(bool $force = false): void
    {
        foreach ($this->iterateStations() as $station) {
            $this->queueForStation($station);
        }
    }

    protected function queueForStation(Entity\Station $station): void
    {
        if (!$station->supportsAutoDjQueue()) {
            return;
        }

        // This feature is not useful for stations without interrupting playlists.
        $hasInterruptingPlaylist = false;
        foreach ($station->getPlaylists() as $playlist) {
            if ($playlist->isPlayable(true)) {
                $hasInterruptingPlaylist = true;
                break;
            }
        }

        if (!$hasInterruptingPlaylist) {
            return;
        }

        // This feature only works on Liquidsoap.
        $backend = $this->adapters->getBackendAdapter($station);

        if (!($backend instanceof Liquidsoap)) {
            return;
        }

        // Check that the interrupting queue is empty first.
        if (!$backend->isQueueEmpty($station, LiquidsoapQueues::Interrupting)) {
            return;
        }

        // Build a queue of interrupting songs to queue up.
        $songsToPlay = $this->queue->getInterruptingQueue($station);

        if (empty($songsToPlay)) {
            return;
        }

        foreach ($songsToPlay as $sq) {
            $event = AnnotateNextSong::fromStationQueue($sq, true);
            $this->eventDispatcher->dispatch($event);

            $track = $event->buildAnnotations();

            $this->logger->debug('Submitting request to AutoDJ.', ['track' => $track]);
            $response = $backend->enqueue($station, LiquidsoapQueues::Interrupting, $track);
            $this->logger->debug('AutoDJ request response', ['response' => $response]);
        }
    }
}
