<?php

namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class HistoryCleanup extends AbstractTask
{
    protected Entity\Repository\SongHistoryRepository $historyRepo;

    protected Entity\Repository\ListenerRepository $listenerRepo;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        Entity\Repository\SongHistoryRepository $historyRepo,
        Entity\Repository\ListenerRepository $listenerRepo
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->historyRepo = $historyRepo;
        $this->listenerRepo = $listenerRepo;
    }

    public function run(bool $force = false): void
    {
        $daysToKeep = (int)$this->settingsRepo->getSetting(
            Entity\Settings::HISTORY_KEEP_DAYS,
            Entity\SongHistory::DEFAULT_DAYS_TO_KEEP
        );

        if ($daysToKeep !== 0) {
            $this->historyRepo->cleanup($daysToKeep);
            $this->listenerRepo->cleanup($daysToKeep);
        }
    }
}
