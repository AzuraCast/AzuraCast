<?php

namespace App\Sync\Task;

use App\Entity;
use App\LockFactory;
use App\Radio\AutoDJ;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class BuildQueue extends AbstractTask
{
    protected AutoDJ $autoDJ;

    protected LockFactory $lockFactory;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger,
        AutoDJ $autoDJ,
        LockFactory $lockFactory
    ) {
        parent::__construct($em, $settingsRepo, $logger);

        $this->autoDJ = $autoDJ;
        $this->lockFactory = $lockFactory;
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

        $lock = $this->lockFactory->createLock('autodj_queue_' . $station->getId(), 60);

        if (!$lock->acquire($force)) {
            return;
        }

        try {
            $this->autoDJ->buildQueue($station);
        } finally {
            $lock->release();
        }
    }
}
