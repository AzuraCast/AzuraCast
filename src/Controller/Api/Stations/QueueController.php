<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractStationApiCrudController<Entity\StationQueue>
 */
class QueueController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationQueue::class;
    protected string $resourceRouteName = 'api:stations:queue:record';

    public function __construct(
        protected Entity\ApiGenerator\StationQueueApiGenerator $queueApiGenerator,
        protected Entity\Repository\StationQueueRepository $queueRepo,
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @OA\Get(path="/station/{station_id}/queue",
     *   tags={"Stations: Queue"},
     *   description="Return information about the upcoming song playback queue.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array",
     *       @OA\Items(ref="#/components/schemas/Api_StationQueueDetailed")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}}
     * )
     *
     * @inheritdoc
     */
    public function listAction(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $station = $request->getStation();
        $query = $this->queueRepo->getUpcomingQuery($station);

        return $this->listPaginatedFromQuery(
            $request,
            $response,
            $query
        );
    }

    /**
     * @OA\Get(path="/station/{station_id}/queue/{id}",
     *   tags={"Stations: Queue"},
     *   description="Retrieve details of a single queued item.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Queue Item ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_StationQueueDetailed")
     *   ),
     *   @OA\Response(response=404, description="Station or Queue ID not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}}
     * )
     *
     * @OA\Delete(path="/station/{station_id}/queue/{id}",
     *   tags={"Stations: Queue"},
     *   description="Delete a single queued item.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Queue Item ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=404, description="Station or Queue ID not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}}
     * )
     */

    /**
     * @param object $record
     * @param ServerRequest $request
     */
    protected function viewRecord(object $record, ServerRequest $request): Entity\Api\StationQueueDetailed
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $router = $request->getRouter();

        /** @var Entity\StationQueue $record */
        $row = ($this->queueApiGenerator)($record);
        $row->resolveUrls($router->getBaseUrl());

        $isInternal = ('true' === $request->getParam('internal', 'false'));

        $apiResponse = new Entity\Api\StationQueueDetailed();
        $apiResponse->fromParentObject($row);

        $apiResponse->autodj_custom_uri = $record->getAutodjCustomUri();
        $apiResponse->log = $record->getLog();

        $apiResponse->links = [
            'self' => (string)$router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], !$isInternal),
        ];

        return $apiResponse;
    }
}
