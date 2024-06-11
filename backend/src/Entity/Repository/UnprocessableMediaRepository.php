<?php

declare(strict_types=1);

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity\StorageLocation;
use App\Entity\UnprocessableMedia;
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
            $record->setError($error);
        }

        $record->setMtime(time());

        $this->em->persist($record);
        $this->em->flush();
    }
}
