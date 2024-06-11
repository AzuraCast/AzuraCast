<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetQuotaAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string|null $type */
        $type = $params['type'] ?? null;

        $typeEnum = StorageLocationTypes::tryFrom($type ?? '')
            ?? StorageLocationTypes::StationMedia;

        $station = $request->getStation();
        $storageLocation = $station->getStorageLocation($typeEnum);

        $numFiles = match ($typeEnum) {
            StorageLocationTypes::StationMedia => $this->getNumStationMedia($station),
            StorageLocationTypes::StationPodcasts => $this->getNumStationPodcastMedia($station),
            default => null,
        };

        return $response->withJson([
            'used' => $storageLocation->getStorageUsed(),
            'used_bytes' => (string)$storageLocation->getStorageUsedBytes(),
            'used_percent' => $storageLocation->getStorageUsePercentage(),
            'available' => $storageLocation->getStorageAvailable(),
            'available_bytes' => (string)$storageLocation->getStorageAvailableBytes(),
            'quota' => $storageLocation->getStorageQuota(),
            'quota_bytes' => (string)$storageLocation->getStorageQuotaBytes(),
            'is_full' => $storageLocation->isStorageFull(),
            'num_files' => $numFiles,
        ]);
    }

    private function getNumStationMedia(Station $station): int
    {
        return (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->getMediaStorageLocation())
            ->getSingleScalarResult();
    }

    private function getNumStationPodcastMedia(Station $station): int
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
