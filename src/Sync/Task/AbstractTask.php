<?php
namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    protected EntityManager $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected LoggerInterface $logger;

    public function __construct(
        EntityManager $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
        $this->logger = $logger;
    }

    abstract public function run($force = false): void;
}
