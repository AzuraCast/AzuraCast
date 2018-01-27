<?php
namespace Controller\Api;

use Entity;

class IndexController extends BaseController
{
    /**
     * Public index for API.
     */
    public function indexAction()
    {
        return $this->redirect($this->url->content('api/index.html'));
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
    public function statusAction()
    {
        return $this->returnSuccess(new Entity\Api\Status);
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
    public function timeAction()
    {
        $this->setCacheLifetime(0);

        $tz_info = \App\Timezone::getInfo();
        return $this->returnSuccess(new Entity\Api\Time($tz_info));
    }
}