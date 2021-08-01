<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\Service\Flow\UploadedFile;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractStationApiCrudController<Entity\StationMount>
 */
class MountsController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationMount::class;
    protected string $resourceRouteName = 'api:stations:mount';

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        protected Entity\Repository\StationMountRepository $mountRepo
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @OA\Get(path="/station/{station_id}/mounts",
     *   tags={"Stations: Mount Points"},
     *   description="List all current mount points.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/StationMount"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/station/{station_id}/mounts",
     *   tags={"Stations: Mount Points"},
     *   description="Create a new mount point.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/station/{station_id}/mount/{id}",
     *   tags={"Stations: Mount Points"},
     *   description="Retrieve details for a single mount point.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Streamer ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/station/{station_id}/mount/{id}",
     *   tags={"Stations: Mount Points"},
     *   description="Update details of a single mount point.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/StationMount")
     *   ),
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Streamer ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/station/{station_id}/mount/{id}",
     *   tags={"Stations: Mount Points"},
     *   description="Delete a single mount point.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="StationMount ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        /** @var Entity\StationMount $record */
        $return = parent::viewRecord($record, $request);

        $station = $request->getStation();
        $frontend = $request->getStationFrontend();
        $router = $request->getRouter();

        $return['links']['intro'] = (string)$router->fromHere(
            route_name:   'api:stations:mounts:intro',
            route_params: ['id' => $record->getId()],
            absolute:     true
        );

        $return['links']['listen'] = (string)Router::resolveUri(
            $router->getBaseUrl(),
            $frontend->getUrlForMount($station, $record),
            true
        );

        return $return;
    }

    public function createAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();

        $parsedBody = (array)$request->getParsedBody();
        $record = $this->editRecord(
            $parsedBody,
            new Entity\StationMount($station)
        );

        if (!empty($parsedBody['intro_file'])) {
            $intro = UploadedFile::fromArray($parsedBody['intro_file'], $station->getRadioTempDir());
            $this->mountRepo->setIntro($record, $intro);
        }

        return $response->withJson($this->viewRecord($record, $request));
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        int $station_id,
        int $id
    ): ResponseInterface {
        $record = $this->getRecord($this->getStation($request), $id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $this->mountRepo->destroy($record);

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }

    /**
     * @inheritDoc
     */
    protected function getStation(ServerRequest $request): Entity\Station
    {
        $station = parent::getStation($request);

        $frontend = $request->getStationFrontend();
        if (!$frontend->supportsMounts()) {
            throw new StationUnsupportedException();
        }

        return $station;
    }
}
