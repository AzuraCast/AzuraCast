<?php
namespace App\Controller\Api\Stations;

use App;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Doctrine\ORM\EntityManager;
use InvalidArgumentException;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class QueueController extends AbstractStationApiCrudController
{
    protected string $entityClass = Entity\SongHistory::class;
    protected string $resourceRouteName = 'api:stations:queue:record';

    protected App\ApiUtilities $apiUtils;

    public function __construct(
        EntityManager $em,
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

        $query = $this->em->createQuery(/** @lang DQL */ 'SELECT sh, sp, s, sm
            FROM App\Entity\SongHistory sh 
            LEFT JOIN sh.song s 
            LEFT JOIN sh.media sm
            LEFT JOIN sh.playlist sp 
            WHERE sh.station_id = :station_id
            AND sh.sent_to_autodj = 0
            AND sh.timestamp_start = 0
            AND sh.timestamp_end = 0
            ORDER BY sh.timestamp_cued DESC')
            ->setParameter('station_id', $station->getId());

        return $this->_listPaginatedFromQuery($request, $response, $query);
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
     */

    /**
     * @inheritdoc
     */
    protected function _viewRecord($record, \App\Http\ServerRequest $request)
    {
        if (!($record instanceof $this->entityClass)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        /** @var Entity\SongHistory $record */
        /** @var Entity\Api\QueuedSong $row */
        $row = $record->api(new Entity\Api\QueuedSong, $this->apiUtils);
        $row->resolveUrls($request->getBaseUrl());

        $row->links = [
            'self' => $request->fromHere($this->resourceRouteName, ['id' => $record->getId()], [], true),
        ];

        return $row;
    }
}
