<?php
namespace Controller\Api;

use Entity;
use App\Http\Request;
use App\Http\Response;

class IndexController extends BaseController
{
    /**
     * Public index for API.
     */
    public function indexAction(Request $request, Response $response): Response
    {
        return $response->withRedirect($this->url->content('api/index.html'));
    }

    /**
     * @SWG\Get(path="/status",
     *   tags={"Miscellaneous"},
     *   description="Returns an affirmative response if the API is active.",
     *   parameters={},
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(ref="#/definitions/Status")
     *   )
     * )
     */
    public function statusAction(Request $request, Response $response): Response
    {
        return $response->withJson(new Entity\Api\Status);
    }

    /**
     * @SWG\Get(path="/time",
     *   tags={"Miscellaneous"},
     *   description="Returns the time (with formatting) in GMT and the user's local time zone, if logged in.",
     *   parameters={},
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(ref="#/definitions/Time")
     *   )
     * )
     */
    public function timeAction(Request $request, Response $response): Response
    {
        $tz_info = \App\Timezone::getInfo();
        return $response->withJson(new Entity\Api\Time($tz_info));
    }
}