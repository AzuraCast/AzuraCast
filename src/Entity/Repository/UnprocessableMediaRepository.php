<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;

class UnprocessableMediaRepository extends Repository
{
    public function clearForPath(
        Entity\StorageLocation $storageLocation,
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
        Entity\StorageLocation $storageLocation,
        string $path,
        ?string $error = null
    ): void {
        $record = $this->repository->findOneBy(
            [
                'storage_location' => $storageLocation,
                'path' => $path,
            ]
        );

        if (!$record instanceof Entity\UnprocessableMedia) {
            $record = new Entity\UnprocessableMedia($storageLocation, $path);
            $record->setError($error);
        }

        $record->setMtime(time());

        $this->em->persist($record);
        $this->em->flush();
    }
}
