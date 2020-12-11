<?php

namespace App\Sync\Task;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    protected EntityManagerInterface $em;

    protected LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
    }

    abstract public function run(bool $force = false): void;
}
