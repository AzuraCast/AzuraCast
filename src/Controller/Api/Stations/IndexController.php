<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\RequestHelper;
use App\Http\ResponseHelper;
use App\Radio\Adapters;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array",
     *       @OA\Items(ref="#/components/schemas/Api_Station")
     *     )
     *   )
     * )
     */
    public function listAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
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

            $api_row->resolveUrls(RequestHelper::getRouter($request)->getBaseUrl());

            if ($api_row->is_public) {
                $stations[] = $api_row;
            }
        }

        return ResponseHelper::withJson($response, $stations);
    }

    /**
     * @OA\Get(path="/station/{station_id}",
     *   tags={"Stations: General"},
     *   description="Return information about a single station.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Station")
     *   ),
     *   @OA\Response(response=404, description="Station not found")
     * )
     */
    public function indexAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $api_response = RequestHelper::getStation($request)->api(RequestHelper::getStationFrontend($request));
        $api_response->resolveUrls(RequestHelper::getRouter($request)->getBaseUrl());

        return ResponseHelper::withJson($response, $api_response);
    }
}
