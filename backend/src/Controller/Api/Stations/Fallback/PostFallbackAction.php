<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Fallback;

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
    path: '/station/{station_id}/fallback',
    operationId: 'postStationFallback',
    description: 'Update the custom fallback track for the station.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
    tags: ['Stations: General'],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
    ],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class PostFallbackAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo
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

        $this->stationRepo->setFallback($station, $flowResponse);

        return $response->withJson(Status::updated());
    }
}
