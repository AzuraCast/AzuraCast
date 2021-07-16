<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\LockFactory;
use App\Radio\AutoDJ;
use Psr\Log\LoggerInterface;

class BuildQueueTask extends AbstractTask
{
    public function __construct(
        protected AutoDJ $autoDJ,
        protected LockFactory $lockFactory,
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger,
    ) {
        parent::__construct($em, $logger);
    }

    public function run(bool $force = false): void
    {
        $stations = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
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

        $this->autoDJ->buildQueue($station, $force);
    }
}
