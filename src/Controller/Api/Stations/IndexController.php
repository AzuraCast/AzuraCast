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
     * @OA\Get(path="/stations",
     *   tags={"Stations: General"},
     *   description="Returns a list of stations.",
     *   parameters={},
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Station")
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
            $api_row = $row->api(
                $this->adapters->getFrontendAdapter($row),
                $this->adapters->getRemoteAdapters($row)
            );

            $api_row->resolveUrls($request->getRouter());

            if ($api_row->is_public) {
                $stations[] = $api_row;
            }
        }

        return $response->withJson($stations);
    }

    /**
     * @OA\Get(path="/station/{station_id}",
     *   tags={"Stations: General"},
     *   description="Return information about a single station.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       ref="#/components/schemas/Station"
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found")
     * )
     */
    public function indexAction(Request $request, Response $response): Response
    {
        $api_response = $request->getStation()->api($request->getStationFrontend());
        $api_response->resolveUrls($request->getRouter());

        return $response->withJson($api_response);
    }
}
