<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use Brick\Math\BigInteger;

/**
 * @extends Repository<Entity\StorageLocation>
 */
final class StorageLocationRepository extends Repository
{
    public function findByType(
        string|Entity\Enums\StorageLocationTypes $type,
        int $id
    ): ?Entity\StorageLocation {
        if ($type instanceof Entity\Enums\StorageLocationTypes) {
            $type = $type->value;
        }

        return $this->repository->findOneBy(
            [
                'type' => $type,
                'id' => $id,
            ]
        );
    }

    /**
     * @param string|Entity\Enums\StorageLocationTypes $type
     *
     * @return Entity\StorageLocation[]
     */
    public function findAllByType(string|Entity\Enums\StorageLocationTypes $type): array
    {
        if ($type instanceof Entity\Enums\StorageLocationTypes) {
            $type = $type->value;
        }

        return $this->repository->findBy(
            [
                'type' => $type,
            ]
        );
    }

    /**
     * @param string|Entity\Enums\StorageLocationTypes $type
     * @param bool $addBlank
     * @param string|null $emptyString
     *
     * @return string[]
     */
    public function fetchSelectByType(
        string|Entity\Enums\StorageLocationTypes $type,
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
        $backupLocations = $this->findAllByType(Entity\Enums\StorageLocationTypes::Backup);

        if (0 === count($backupLocations)) {
            $record = new Entity\StorageLocation(
                Entity\Enums\StorageLocationTypes::Backup,
                Entity\Enums\StorageLocationAdapters::Local
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

        switch ($storageLocation->getTypeEnum()) {
            case Entity\Enums\StorageLocationTypes::StationMedia:
                $qb->where('s.media_storage_location = :storageLocation')
                    ->setParameter('storageLocation', $storageLocation);
                break;

            case Entity\Enums\StorageLocationTypes::StationRecordings:
                $qb->where('s.recordings_storage_location = :storageLocation')
                    ->setParameter('storageLocation', $storageLocation);
                break;

            case Entity\Enums\StorageLocationTypes::StationPodcasts:
                $qb->where('s.podcasts_storage_location = :storageLocation')
                    ->setParameter('storageLocation', $storageLocation);
                break;

            case Entity\Enums\StorageLocationTypes::Backup:
                return [];
        }

        return $qb->getQuery()->execute();
    }

    public function addStorageUsed(
        Entity\StorageLocation $storageLocation,
        BigInteger|int|string $newStorageAmount
    ): void {
        $storageLocation->addStorageUsed($newStorageAmount);
        $this->em->persist($storageLocation);
        $this->em->flush();
    }

    public function removeStorageUsed(
        Entity\StorageLocation $storageLocation,
        BigInteger|int|string $amountToRemove
    ): void {
        $storageLocation->removeStorageUsed($amountToRemove);
        $this->em->persist($storageLocation);
        $this->em->flush();
    }
}
