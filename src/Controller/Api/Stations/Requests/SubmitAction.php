<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Requests;

use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\Repository\SettingsRepository;
use App\Entity\Repository\StationRequestRepository;
use App\Entity\User;
use App\Exception\InvalidRequestAttribute;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Exception;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Post(
        path: '/station/{station_id}/request/{request_id}',
        operationId: 'submitSongRequest',
        description: 'Submit a song request.',
        tags: ['Stations: Song Requests'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'request_id',
                description: 'The requestable song ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class SubmitAction
{
    public function __construct(
        private readonly StationRequestRepository $requestRepo,
        private readonly SettingsRepository $settingsRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id,
        string $media_id
    ): ResponseInterface {
        $station = $request->getStation();

        try {
            $user = $request->getUser();
        } catch (InvalidRequestAttribute) {
            $user = null;
        }

        $isAuthenticated = ($user instanceof User);

        try {
            $ip = $this->settingsRepo->readSettings()->getIp($request);

            $this->requestRepo->submit(
                $station,
                $media_id,
                $isAuthenticated,
                $ip,
                $request->getHeaderLine('User-Agent')
            );

            return $response->withJson(Status::success());
        } catch (Exception $e) {
            return $response->withStatus(400)
                ->withJson(Error::fromException($e));
        }
    }
}
