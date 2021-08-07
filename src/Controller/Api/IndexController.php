<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    /**
     * Public index for API.
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        return $response->withRedirect('/static/api/index.html');
    }

    /**
     * @OA\Get(path="/status",
     *   tags={"Miscellaneous"},
     *   description="Returns an affirmative response if the API is active.",
     *   parameters={},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_SystemStatus")
     *   )
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function statusAction(ServerRequest $request, Response $response): ResponseInterface
    {
        return $response->withJson(new Entity\Api\SystemStatus());
    }

    /**
     * @OA\Get(path="/time",
     *   tags={"Miscellaneous"},
     *   description="Returns the time (with formatting) in GMT and the user's local time zone, if logged in.",
     *   parameters={},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Time")
     *   )
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function timeAction(ServerRequest $request, Response $response): ResponseInterface
    {
        return $response->withJson(new Entity\Api\Time());
    }
}
