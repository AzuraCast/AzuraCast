<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use Azura\DoctrineBatchUtils\ReadWriteBatchIteratorAggregate;
use Psr\Log\LoggerInterface;

abstract class AbstractTask implements ScheduledTaskInterface
{
    public function __construct(
        protected ReloadableEntityManagerInterface $em,
        protected LoggerInterface $logger
    ) {
    }

    public static function isLongTask(): bool
    {
        return false;
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
     * @param Entity\Enums\StorageLocationTypes $type
     *
     * @return ReadWriteBatchIteratorAggregate|Entity\StorageLocation[]
     */
    protected function iterateStorageLocations(Entity\Enums\StorageLocationTypes $type): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT sl
                    FROM App\Entity\StorageLocation sl
                    WHERE sl.type = :type
                DQL
            )->setParameter('type', $type->value),
            1
        );
    }
}
