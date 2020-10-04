<?php
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

class QueueController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\StationQueue::class;
    protected string $resourceRouteName = 'api:stations:queue:record';

    protected App\ApiUtilities $apiUtils;

    public function __construct(
        EntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator,
        App\ApiUtilities $apiUtils
    ) {
        parent::__construct($em, $serializer, $validator);

        $this->apiUtils = $apiUtils;
    }

    /**
     * @OA\Get(path="/station/{station_id}/queue",
     *   tags={"Stations: Queue"},
     *   description="Return information about the upcoming song playback queue.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array",
     *       @OA\Items(ref="#/components/schemas/Api_QueuedSong")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}}
     * )
     *
     * @inheritdoc
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT sq, sp, sm
            FROM App\Entity\StationQueue sq 
            LEFT JOIN sq.media sm
            LEFT JOIN sq.playlist sp 
            WHERE sq.station = :station
            ORDER BY sq.timestamp_cued ASC')
            ->setParameter('station', $station);

        return $this->listPaginatedFromQuery($request, $response, $query);
    }

    /**
     * @OA\Get(path="/station/{station_id}/queue/{id}",
     *   tags={"Stations: Queue"},
     *   description="Retrieve details of a single queued item.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Queue Item ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_QueuedSong")
     *   ),
     *   @OA\Response(response=404, description="Station or Queue ID not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}}
     * )
     *
     * @OA\Delete(path="/station/{station_id}/queue/{id}",
     *   tags={"Stations: Queue"},
     *   description="Delete a single queued item.",
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
     *
     * @param mixed $record
     * @param ServerRequest $request
     *
     * @return Entity\Api\QueuedSong
     * @throws App\Exception
     */

    protected function viewRecord($record, ServerRequest $request)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $router = $request->getRouter();

        /** @var Entity\StationQueue $record */
        /** @var Entity\Api\QueuedSong $row */
        $row = $record->api($this->apiUtils);
        $row->resolveUrls($router->getBaseUrl());

        $isInternal = ('true' === $request->getParam('internal', 'false'));

        $row->links = [
            'self' => $router->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], !$isInternal),
        ];

        return $row;
    }
}
