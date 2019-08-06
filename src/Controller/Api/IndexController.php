<?php
namespace App\Controller\Api;

use App\Entity;
use App\Http\ResponseHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class IndexController
{
    /**
     * Public index for API.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return \App\Http\ResponseHelper::withRedirect($response, '/static/api/index.html');
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function statusAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        return ResponseHelper::withJson($response, new Entity\Api\SystemStatus);
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function timeAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $tz_info = \Azura\Timezone::getInfo();
        return ResponseHelper::withJson($response, new Entity\Api\Time($tz_info));
    }
}
