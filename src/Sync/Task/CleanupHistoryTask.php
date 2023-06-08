<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\SettingsAwareTrait;
use App\Entity\Repository\ListenerRepository;
use App\Entity\Repository\SongHistoryRepository;
use App\Entity\Repository\StationQueueRepository;
use App\Entity\StationQueue;

final class CleanupHistoryTask extends AbstractTask
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly SongHistoryRepository $historyRepo,
        private readonly StationQueueRepository $queueRepo,
        private readonly ListenerRepository $listenerRepo,
    ) {
    }

    public static function getSchedulePattern(): string
    {
        return '17 * * * *';
    }

    public function run(bool $force = false): void
    {
        // Clear station queue independent of history settings.
        $this->queueRepo->cleanup(StationQueue::DAYS_TO_KEEP);

        // Clean up history and listeners according to user settings.
        $daysToKeep = $this->readSettings()->getHistoryKeepDays();
        if (0 !== $daysToKeep) {
            $this->historyRepo->cleanup($daysToKeep);
            $this->listenerRepo->cleanup($daysToKeep);
        }
    }
}
