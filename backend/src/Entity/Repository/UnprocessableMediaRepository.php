<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\StorageLocation;
use App\Entity\UnprocessableMedia;
use App\Utilities\Types;
use Doctrine\ORM\AbstractQuery;
use Generator;

/**
 * @extends Repository<UnprocessableMedia>
 */
final class UnprocessableMediaRepository extends Repository
{
    protected string $entityClass = UnprocessableMedia::class;

    public function findByPath(
        string $path,
        StorageLocation $storageLocation
    ): ?UnprocessableMedia {
        /** @var UnprocessableMedia|null $record */
        $record = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'path' => $path,
            ]
        );

        return $record;
    }

    /**
     * @return array<array-key, int> The modification time of each unprocessable path in the storage location,
     *                               keyed by path (numeric paths become integer keys).
     */
    public function getMtimesByPath(StorageLocation $storageLocation): array
    {
        $query = $this->em->createQuery(
            <<<'DQL'
                SELECT upm.path, upm.mtime
                FROM App\Entity\UnprocessableMedia upm
                WHERE upm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $storageLocation);

        $mtimes = [];
        foreach ($query->toIterable([], AbstractQuery::HYDRATE_ARRAY) as $row) {
            $mtimes[(string)$row['path']] = Types::int($row['mtime']);
        }

        return $mtimes;
    }

    public function iteratePaths(array $paths, StorageLocation $storageLocation): Generator
    {
        foreach ($paths as $path) {
            $record = $this->findByPath($path, $storageLocation);
            if ($record instanceof UnprocessableMedia) {
                yield $path => $record;
            }
        }
    }

    public function clearForPath(
        StorageLocation $storageLocation,
        string $path
    ): void {
        $this->em->createQuery(
            <<<'DQL'
                DELETE FROM App\Entity\UnprocessableMedia upm
                WHERE upm.storage_location = :storageLocation
                AND upm.path = :path
            DQL
        )->setParameter('storageLocation', $storageLocation)
            ->setParameter('path', $path)
            ->execute();
    }

    public function setForPath(
        StorageLocation $storageLocation,
        string $path,
        ?string $error = null
    ): void {
        $record = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'path' => $path,
            ]
        );

        if (!$record instanceof UnprocessableMedia) {
            $record = new UnprocessableMedia($storageLocation, $path);
            $record->error = $error;
        }

        $record->mtime = time() + 5;

        $this->em->persist($record);
        $this->em->flush();
    }
}
