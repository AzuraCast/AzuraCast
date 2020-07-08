<?php
namespace App\Sync\Task;

use App\Entity;
use App\Lock\LockManager;
use App\Radio\AutoDJ;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BuildQueue extends AbstractTask
{
    protected AutoDJ $autoDJ;

    protected LockManager $lockManager;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        AutoDJ $autoDJ,
        LockManager $lockManager
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->autoDJ = $autoDJ;
        $this->lockManager = $lockManager;
    }

    public function run(bool $force = false): void
    {
        $stations = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);

        foreach ($stations as $station) {
            $this->processStation($station, $force);
        }
    }

    public function processStation(
        Entity\Station $station,
        bool $force = false
    ): void {
        if ($station->useManualAutoDJ()) {
            return;
        }

        $lock = $this->lockManager->getLock('autodj_queue_' . $station->getId(), 60, $force);
        $lock->run(function () use ($station) {
            $this->autoDJ->buildQueue($station);
        });
    }
}