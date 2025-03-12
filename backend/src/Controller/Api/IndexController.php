<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\Api\SystemStatus;
use App\Entity\Api\Time;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

final class IndexController
{
    #[OA\Get(
        path: '/status',
        operationId: 'getStatus',
        summary: 'Returns an affirmative response if the API is active.',
        security: [],
        tags: [OpenApi::TAG_PUBLIC_MISC],
        parameters: [],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: SystemStatus::class)
            ),
        ]
    )]
    public function statusAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        return $response->withJson(new SystemStatus());
    }

    #[OA\Get(
        path: '/time',
        operationId: 'getTime',
        description: "Returns the time (with formatting) in GMT and the user's local time zone, if logged in.",
        security: [],
        tags: [OpenApi::TAG_PUBLIC_MISC],
        parameters: [],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: Time::class)
            ),
        ]
    )]
    public function timeAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        return $response->withJson(new Time());
    }
}
