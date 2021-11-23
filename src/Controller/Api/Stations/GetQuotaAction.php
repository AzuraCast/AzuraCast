<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;

class GetQuotaAction
{
    public function __construct(
        protected EntityManagerInterface $em
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $type = Entity\StorageLocation::TYPE_STATION_MEDIA
    ): ResponseInterface {
        $station = $request->getStation();
        $storageLocation = $station->getStorageLocation($type);

        $numFiles = match ($type) {
            Entity\StorageLocation::TYPE_STATION_MEDIA => $this->getNumStationMedia($station),
            Entity\StorageLocation::TYPE_STATION_PODCASTS => $this->getNumStationPodcastMedia($station),
            default => null,
        };

        return $response->withJson([
            'used'            => $storageLocation->getStorageUsed(),
            'used_bytes'      => (string)$storageLocation->getStorageUsedBytes(),
            'used_percent'    => $storageLocation->getStorageUsePercentage(),
            'available'       => $storageLocation->getStorageAvailable(),
            'available_bytes' => (string)$storageLocation->getStorageAvailableBytes(),
            'quota'           => $storageLocation->getStorageQuota(),
            'quota_bytes'     => (string)$storageLocation->getStorageQuotaBytes(),
            'is_full'         => $storageLocation->isStorageFull(),
            'num_files'       => $numFiles,
        ]);
    }

    protected function getNumStationMedia(Entity\Station $station): int
    {
        return (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->getSingleScalarResult();
    }

    protected function getNumStationPodcastMedia(Entity\Station $station): int
    {
        return (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(pm.id) FROM App\Entity\PodcastMedia pm
                WHERE pm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getPodcastsStorageLocation())
            ->getSingleScalarResult();
    }
}
