<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Fallback;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/fallback',
    operationId: 'deleteStationFallback',
    summary: 'Removes the custom fallback track for a station.',
    tags: [OpenApi::TAG_STATIONS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class DeleteFallbackAction implements SingleActionInterface
{
    public function __construct(
        private StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $this->stationRepo->clearFallback($station);

        return $response->withJson(Status::deleted());
    }
}
