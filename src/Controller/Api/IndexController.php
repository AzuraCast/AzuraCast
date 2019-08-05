<?php
namespace App\Controller\Api;

use App\Entity;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ResponseInterface;

class IndexController
{
    /**
     * Public index for API.
     */
    public function indexAction(Request $request, Response $response): ResponseInterface
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
     */
    public function statusAction(Request $request, Response $response): ResponseInterface
    {
        return $response->withJson(new Entity\Api\SystemStatus);
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
     */
    public function timeAction(Request $request, Response $response): ResponseInterface
    {
        $tz_info = \Azura\Timezone::getInfo();
        return $response->withJson(new Entity\Api\Time($tz_info));
    }
}
