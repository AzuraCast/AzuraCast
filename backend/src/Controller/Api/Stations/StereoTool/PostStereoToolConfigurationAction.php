<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Flow;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/station/{station_id}/stereo-tool-configuration',
    operationId: 'postStereoToolConfiguration',
    summary: 'Update the Stereo Tool configuration file for a station.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
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
final readonly class PostStereoToolConfigurationAction implements SingleActionInterface
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

        $flowResponse = Flow::process($request, $response, $station->getRadioTempDir());
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $this->stationRepo->setStereoToolConfiguration($station, $flowResponse);

        return $response->withJson(Status::updated());
    }
}
