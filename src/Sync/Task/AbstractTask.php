<?php

namespace App\Sync\Task;

use App\Doctrine\BatchIteratorAggregate;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
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

    /**
     * @return BatchIteratorAggregate|Entity\Station[]
     */
    protected function iterateStations(): BatchIteratorAggregate
    {
        return BatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT s FROM App\Entity\Station s
                DQL
            ),
            1
        );
    }

    /**
     * @param string $type
     *
     * @return BatchIteratorAggregate|Entity\StorageLocation[]
     */
    protected function iterateStorageLocations(string $type): BatchIteratorAggregate
    {
        return BatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT sl
                    FROM App\Entity\StorageLocation sl
                    WHERE sl.type = :type
                DQL
            )->setParameter('type', $type),
            1
        );
    }
}
