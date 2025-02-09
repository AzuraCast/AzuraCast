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
        path: '/station/{station_id}/quota/{type}',
        operationId: 'getQuota',
        description: 'Get the current usage and quota for a given station storage location.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: General'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'type',
                description: 'The storage location type (i.e. station_media, station_recordings, station_podcasts)',
                in: 'path',
                required: false,
                schema: new OA\Schema(type: 'string', default: 'station_media')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Api_StationQuota',
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
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
