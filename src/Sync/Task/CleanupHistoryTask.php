<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;

class CleanupHistoryTask extends AbstractTask
{
    protected Entity\Repository\SongHistoryRepository $historyRepo;

    protected Entity\Repository\ListenerRepository $listenerRepo;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Repository\SettingsRepository $settingsRepo,
        Entity\Repository\SongHistoryRepository $historyRepo,
        Entity\Repository\ListenerRepository $listenerRepo
    ) {
        parent::__construct($em, $logger);

        $this->settingsRepo = $settingsRepo;
        $this->historyRepo = $historyRepo;
        $this->listenerRepo = $listenerRepo;
    }

    public function run(bool $force = false): void
    {
        $settings = $this->settingsRepo->readSettings();
        $daysToKeep = $settings->getHistoryKeepDays();

        if ($daysToKeep !== 0) {
            $this->historyRepo->cleanup($daysToKeep);
            $this->listenerRepo->cleanup($daysToKeep);
        }
    }
}
