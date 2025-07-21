<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Streamers\Broadcasts;

use App\Controller\Api\Stations\Streamers\BroadcastsController;
use App\Controller\SingleActionInterface;
use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\Api\GenericBatchResult;
use App\Entity\StationStreamerBroadcast;
use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Throwable;

#[OA\Post(
    path: '/station/{station_id}/streamer/{id}/broadcasts/batch',
    operationId: 'postStationStreamerBroadcastsBatch',
    summary: 'Perform batch actions on the specified broadcasts.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'do',
                    description: 'The action to take with the specified rows.',
                    type: 'string',
                    enum: ['delete']
                ),
                new OA\Property(
                    property: 'rows',
                    description: 'The IDs to perform batch actions on.',
                    type: 'array',
                    items: new OA\Items(
                        type: 'integer',
                        format: 'int64'
                    ),
                ),
            ]
        )
    ),
    tags: [OpenApi::TAG_STATIONS_STREAMERS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'id',
            description: 'Streamer ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'integer', format: 'int64')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: GenericBatchResult::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class BatchAction extends BroadcastsController implements SingleActionInterface
{
    private const int BATCH_SIZE = 50;

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $id = Types::int($params['id'] ?? null);

        $station = $request->getStation();
        $streamer = $this->getStreamer($station, $id);

        if (null === $streamer) {
            throw NotFoundException::generic();
        }

        $parsedBody = (array)$request->getParsedBody();

        if (!isset($parsedBody['rows']) || !isset($parsedBody['do'])) {
            throw new InvalidArgumentException('No rows and/or action specified.');
        }

        $rowsQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT ssb
                FROM App\Entity\StationStreamerBroadcast ssb
                WHERE ssb.streamer = :streamer
                AND ssb.station = :station
                AND ssb.id IN (:ids)
                ORDER BY ssb.timestampStart DESC
            DQL
        )->setParameter('station', $station)
            ->setParameter('streamer', $streamer)
            ->setParameter('ids', Types::array($parsedBody['rows']));

        $result = match (Types::string($parsedBody['do'])) {
            'delete' => $this->doDelete($request, $rowsQuery),
            default => throw new InvalidArgumentException('Invalid batch action specified.')
        };

        if ($this->em->isOpen()) {
            $this->em->clear();
        }

        return $response->withJson($result);
    }

    private function doDelete(
        ServerRequest $request,
        Query $rowsQuery
    ): GenericBatchResult {
        $result = new GenericBatchResult();

        $fsRecordings = $this->stationFilesystems->getRecordingsFilesystem($request->getStation());

        /** @var ReadWriteBatchIteratorAggregate<array-key, StationStreamerBroadcast> $rows */
        $rows = ReadWriteBatchIteratorAggregate::fromQuery($rowsQuery, self::BATCH_SIZE);

        foreach ($rows as $row) {
            $id = $row->id;
            $result->records[] = [
                'id' => $id,
                'title' => (string)$row,
            ];

            try {
                $recordingPath = $row->recordingPath;
                if (!empty($recordingPath)) {
                    $fsRecordings->delete($recordingPath);
                }

                $this->em->remove($row);
            } catch (Throwable $e) {
                $result->errors[] = sprintf('%s: %s', $row, $e);
            }
        }

        return $result;
    }
}
