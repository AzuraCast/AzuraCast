<?php

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    protected ReloadableEntityManagerInterface $em;

    protected LoggerInterface $logger;

    public function __construct(
        ReloadableEntityManagerInterface $em,
        LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
    }

    abstract public function run(bool $force = false): void;
}
