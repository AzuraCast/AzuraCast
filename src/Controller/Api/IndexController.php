<?php
namespace App\Controller\Api;

use App\Entity;
use App\Http\Request;
use App\Http\Response;

class IndexController
{
    /**
     * Public index for API.
     */
    public function indexAction(Request $request, Response $response): Response
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
     *     @OA\Schema(ref="#/components/schemas/SystemStatus")
     *   )
     * )
     */
    public function statusAction(Request $request, Response $response): Response
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
     *     @OA\Schema(ref="#/components/schemas/Time")
     *   )
     * )
     */
    public function timeAction(Request $request, Response $response): Response
    {
        $tz_info = \App\Timezone::getInfo();
        return $response->withJson(new Entity\Api\Time($tz_info));
    }
}
