<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Psr\Log\LoggerInterface;

abstract class AbstractTask
{
    public function __construct(
        protected ReloadableEntityManagerInterface $em,
        protected LoggerInterface $logger
    ) {
    }

    abstract public function run(bool $force = false): void;

    /**
     * @return ReadWriteBatchIteratorAggregate|Entity\Station[]
     */
    protected function iterateStations(): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
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
     * @return ReadWriteBatchIteratorAggregate|Entity\StorageLocation[]
     */
    protected function iterateStorageLocations(string $type): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
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
