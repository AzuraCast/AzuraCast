<?php
namespace App\Controller\Api\Stations;

use App\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class IndexController
{
    /** @var EntityManager */
    protected $em;

    /** @var Adapters */
    protected $adapters;

    /**
     * StationsController constructor.
     * @param EntityManager $em
     * @param Adapters $adapters
     */
    public function __construct(EntityManager $em, Adapters $adapters)
    {
        $this->em = $em;
        $this->adapters = $adapters;
    }

    /**
     * @SWG\Get(path="/stations",
     *   tags={"Stations: General"},
     *   description="Returns a list of stations.",
     *   parameters={},
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       type="array",
     *       @SWG\Items(ref="#/definitions/Station")
     *     )
     *   )
     * )
     */
    public function listAction(Request $request, Response $response): Response
    {
        $stations_raw = $this->em->getRepository(Entity\Station::class)
            ->findBy(['is_enabled' => 1]);

        $stations = [];
        foreach ($stations_raw as $row) {
            /** @var Entity\Station $row */

            $frontend_adapter = $this->adapters->getFrontendAdapter($row);
            $api_row = $row->api($frontend_adapter);
            if ($api_row->is_public) {
                $stations[] = $api_row;
            }
        }

        return $response->withJson($stations);
    }

    /**
     * @SWG\Get(path="/station/{station_id}",
     *   tags={"Stations: General"},
     *   description="Return information about a single station.",
     *   @SWG\Parameter(ref="#/parameters/station_id_required"),
     *   @SWG\Response(
     *     response=200,
     *     description="Success",
     *     @SWG\Schema(
     *       ref="#/definitions/Station"
     *     )
     *   ),
     *   @SWG\Response(response=404, description="Station not found")
     * )
     */
    public function indexAction(Request $request, Response $response): Response
    {
        $api_response = $request->getStation()->api($request->getStationFrontend());
        return $response->withJson($api_response);
    }
}
