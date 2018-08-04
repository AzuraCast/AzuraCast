<?php
namespace App\Controller\Api;

use App\Url;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class IndexController
{
    /** @var Url */
    protected $url;

    /**
     * IndexController constructor.
     * @param Url $url
     */
    public function __construct(Url $url)
    {
        $this->url = $url;
    }

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
     *     @SWG\Schema(ref="#/definitions/SystemStatus")
     *   )
     * )
     */
    public function statusAction(Request $request, Response $response): Response
    {
        return $response->withJson(new Entity\Api\SystemStatus);
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
