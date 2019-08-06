<?php
namespace App\Sync\Task;

use Doctrine\ORM\EntityManager;
use Interop\Container\ContainerInterface;
use Monolog\Logger;

abstract class AbstractTask
{
    /** @var EntityManager */
    protected $em;

    /** @var Logger */
    protected $logger;

    /**
     * @param EntityManager $em
     * @param Logger $logger
     */
    public function __construct(EntityManager $em, Logger $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    abstract public function run($force = false): void;
}
