<?php

namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
        $this->logger = $logger;
    }

    abstract public function run(bool $force = false): void;
}
