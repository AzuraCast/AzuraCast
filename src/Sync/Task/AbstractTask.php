<?php

declare(strict_types=1);

namespace App\Sync\Task;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\Enums\StorageLocationTypes;

abstract class AbstractTask implements ScheduledTaskInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public static function isLongTask(): bool
    {
        return false;
    }

    abstract public function run(bool $force = false): void;

    /**
     * @return ReadWriteBatchIteratorAggregate<int, \App\Entity\Station>
     */
    protected function iterateStations(): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT s FROM App\\App\Entity\Station s
                DQL
            ),
            1
        );
    }

    /**
     * @param \App\Entity\Enums\StorageLocationTypes $type
     *
     * @return ReadWriteBatchIteratorAggregate<int, \App\Entity\StorageLocation>
     */
    protected function iterateStorageLocations(StorageLocationTypes $type): ReadWriteBatchIteratorAggregate
    {
        return ReadWriteBatchIteratorAggregate::fromQuery(
            $this->em->createQuery(
                <<<'DQL'
                    SELECT sl
                    FROM App\\App\Entity\StorageLocation sl
                    WHERE sl.type = :type
                DQL
            )->setParameter('type', $type->value),
            1
        );
    }
}
