<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/station/{station_id}/stereo-tool-configuration',
    operationId: 'deleteStereoToolConfiguration',
    summary: 'Removes the Stereo Tool configuration file for a station.',
    tags: [OpenApi::TAG_STATIONS_BROADCASTING],
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
final readonly class DeleteStereoToolConfigurationAction implements SingleActionInterface
{
    public function __construct(
        private StationRepository $stationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $this->stationRepo->clearStereoToolConfiguration($station);

        return $response->withJson(Status::deleted());
    }
}
