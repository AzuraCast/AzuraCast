<?php

namespace App\Sync\Task;

use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;

    protected Entity\Settings $settings;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        Entity\Settings $settings
    ) {
        $this->em = $em;
        $this->logger = $logger;
        $this->settings = $settings;
    }

    abstract public function run(bool $force = false): void;
}
