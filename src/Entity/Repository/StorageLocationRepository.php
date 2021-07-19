<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

/**
 * @extends Repository<Entity\StorageLocation>
 */
class StorageLocationRepository extends Repository
{
    public function findByType(string $type, int $id): ?Entity\StorageLocation
    {
        return $this->repository->findOneBy(
            [
                'type' => $type,
                'id' => $id,
            ]
        );
    }

    /**
     * @param string $type
     *
     * @return Entity\StorageLocation[]
     */
    public function findAllByType(string $type): array
    {
        return $this->repository->findBy(
            [
                'type' => $type,
            ]
        );
    }

    /**
     * @param string $type
     * @param bool $addBlank
     * @param string|null $emptyString
     *
     * @return string[]
     */
    public function fetchSelectByType(
        string $type,
        bool $addBlank = false,
        ?string $emptyString = null
    ): array {
        $select = [];

        if ($addBlank) {
            $emptyString ??= __('None');
            $select[''] = $emptyString;
        }

        foreach ($this->findAllByType($type) as $storageLocation) {
            $select[$storageLocation->getId()] = (string)$storageLocation;
        }

        return $select;
    }

    public function createDefaultStorageLocations(): void
    {
        $backupLocations = $this->findAllByType(Entity\StorageLocation::TYPE_BACKUP);

        if (0 === count($backupLocations)) {
            $record = new Entity\StorageLocation(
                Entity\StorageLocation::TYPE_BACKUP,
                Entity\StorageLocation::ADAPTER_LOCAL
            );
            $record->setPath(Entity\StorageLocation::DEFAULT_BACKUPS_PATH);
            $this->em->persist($record);
        }

        $this->em->flush();
    }

    /**
     * @param Entity\StorageLocation $storageLocation
     *
     * @return Entity\Station[]
     */
    public function getStationsUsingLocation(Entity\StorageLocation $storageLocation): array
    {
        $qb = $this->em->createQueryBuilder()
            ->select('s')
            ->from(Entity\Station::class, 's');

        switch ($storageLocation->getType()) {
            case Entity\StorageLocation::TYPE_STATION_MEDIA:
                $qb->where('s.media_storage_location = :storageLocation')
                    ->setParameter('storageLocation', $storageLocation);
                break;

            case Entity\StorageLocation::TYPE_STATION_RECORDINGS:
                $qb->where('s.recordings_storage_location = :storageLocation')
                    ->setParameter('storageLocation', $storageLocation);
                break;

            case Entity\StorageLocation::TYPE_STATION_PODCASTS:
                $qb->where('s.podcasts_storage_location = :storageLocation')
                    ->setParameter('storageLocation', $storageLocation);
                break;

            case Entity\StorageLocation::TYPE_BACKUP:
            default:
                return [];
        }

        return $qb->getQuery()->execute();
    }
}
