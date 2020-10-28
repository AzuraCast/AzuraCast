<?php

namespace App\Entity\Repository;

use App\Doctrine\Repository;
use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Settings;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Serializer\Serializer;

class StorageLocationRepository extends Repository
{
    protected FilesystemManager $filesystem;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        Settings $settings,
        LoggerInterface $logger,
        FilesystemManager $filesystem
    ) {
        parent::__construct($em, $serializer, $settings, $logger);

        $this->filesystem = $filesystem;
    }

    public function flushAssociatedCaches(Entity\StorageLocation $storageLocation): void
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

            case Entity\StorageLocation::TYPE_BACKUP:
            default:
                return;
        }

        $stations = $qb->getQuery()->execute();

        foreach ($stations as $station) {
            $fs = $this->filesystem->getForStation($station, true);
            $fs->flushAllCaches(false);
        }
    }
}
