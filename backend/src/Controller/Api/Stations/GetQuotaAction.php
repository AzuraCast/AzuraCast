<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\StationQuota;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/station/{station_id}/quota',
        operationId: 'getQuota',
        summary: 'Get the current usage and quota for a given station storage location.',
        tags: [OpenApi::TAG_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: StationQuota::class,
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/station/{station_id}/quota/{type}',
        operationId: 'getQuotaOfType',
        summary: 'Get the current usage and quota for a given station storage location.',
        tags: [OpenApi::TAG_STATIONS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'type',
                description: 'The storage location type (i.e. station_media, station_recordings, station_podcasts)',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: StationQuota::class,
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
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

        return $response->withJson(
            StationQuota::fromStorageLocation($storageLocation, $numFiles)
        );
    }

    private function getNumStationMedia(Station $station): int
    {
        return (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(sm.id) FROM App\Entity\StationMedia sm
                WHERE sm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->media_storage_location)
            ->getSingleScalarResult();
    }

    private function getNumStationPodcastMedia(Station $station): int
    {
        return (int)$this->em->createQuery(
            <<<'DQL'
                SELECT COUNT(pm.id) FROM App\Entity\PodcastMedia pm
                WHERE pm.storage_location = :storageLocation
            DQL
        )->setParameter('storageLocation', $station->podcasts_storage_location)
            ->getSingleScalarResult();
    }
}
