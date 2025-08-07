<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Podcasts;

use App\Controller\Api\Stations\PodcastEpisodesController;
use App\Controller\SingleActionInterface;
use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Doctrine\ReadWriteBatchIteratorAggregate;
use App\Entity\Api\PodcastBatchResult;
use App\Entity\ApiGenerator\PodcastEpisodeApiGenerator;
use App\Entity\PodcastEpisode;
use App\Entity\Repository\PodcastEpisodeRepository;
use App\Exception\ValidationException;
use App\Flysystem\StationFilesystems;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

#[OA\Post(
    path: '/station/{station_id}/podcast/{podcast_id}/batch',
    operationId: 'postStationPodcastBatch',
    summary: 'Import the contents of an uploaded playlist (PLS/M3U) file into the specified playlist.',
    requestBody: new OA\RequestBody(
        content: new OA\JsonContent(
            properties: [
                new OA\Property(
                    property: 'do',
                    description: 'The action to take with the specified rows.',
                    type: 'string',
                    enum: ['list', 'delete', 'edit']
                ),
                new OA\Property(
                    property: 'episodes',
                    description: 'The IDs to perform batch actions on.',
                    type: 'array',
                    items: new OA\Items(
                        type: 'string',
                    ),
                ),
            ]
        )
    ),
    tags: [OpenApi::TAG_STATIONS_PLAYLISTS],
    parameters: [
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'podcast_id',
            description: 'Podcast ID',
            in: 'path',
            required: true,
            schema: new OA\Schema(type: 'string')
        ),
    ],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: PodcastBatchResult::class
                )
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class BatchAction extends PodcastEpisodesController implements SingleActionInterface
{
    private const int BATCH_SIZE = 50;

    public function __construct(
        PodcastEpisodeRepository $episodeRepository,
        PodcastEpisodeApiGenerator $episodeApiGen,
        Serializer $serializer,
        ValidatorInterface $validator,
        private readonly StationFilesystems $stationFilesystems
    ) {
        parent::__construct($episodeRepository, $episodeApiGen, $serializer, $validator);
    }

    public function __invoke(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        $podcast = $request->getPodcast();

        $parsedBody = (array)$request->getParsedBody();

        if (!isset($parsedBody['episodes']) || !isset($parsedBody['do'])) {
            throw new InvalidArgumentException('No episodes and/or action specified.');
        }

        $rowsQuery = $this->em->createQuery(
            <<<'DQL'
                SELECT e, pm
                FROM App\Entity\PodcastEpisode e
                LEFT JOIN e.media pm
                WHERE e.podcast = :podcast
                AND e.id IN (:ids)
                ORDER BY e.publish_at DESC
            DQL
        )->setParameter('podcast', $podcast)
            ->setParameter('ids', Types::array($parsedBody['episodes']));

        $result = match (Types::string($parsedBody['do'])) {
            'list' => $this->doList($request, $rowsQuery),
            'delete' => $this->doDelete($request, $rowsQuery),
            'edit' => $this->doEdit($request, $rowsQuery, Types::array($parsedBody['records'])),
            default => throw new InvalidArgumentException('Invalid batch action specified.')
        };

        if ($this->em->isOpen()) {
            $this->em->clear();
        }

        return $response->withJson($result);
    }

    private function doList(
        ServerRequest $request,
        Query $rowsQuery
    ): PodcastBatchResult {
        $result = new PodcastBatchResult();
        $result->records = [];

        /** @var ReadOnlyBatchIteratorAggregate<array-key, PodcastEpisode> $rows */
        $rows = ReadOnlyBatchIteratorAggregate::fromQuery($rowsQuery, self::BATCH_SIZE);

        foreach ($rows as $row) {
            $result->episodes[] = [
                'id' => $row->id,
                'title' => $row->title,
            ];
            $result->records[] = $this->viewRecord($row, $request);
        }

        return $result;
    }

    private function doDelete(
        ServerRequest $request,
        Query $rowsQuery
    ): PodcastBatchResult {
        $result = new PodcastBatchResult();

        $fsPodcasts = $this->stationFilesystems->getPodcastsFilesystem($request->getStation());

        /** @var ReadWriteBatchIteratorAggregate<array-key, PodcastEpisode> $rows */
        $rows = ReadWriteBatchIteratorAggregate::fromQuery($rowsQuery, self::BATCH_SIZE);

        foreach ($rows as $row) {
            $id = $row->id;
            $title = $row->title;

            $result->episodes[] = [
                'id' => $id,
                'title' => $title,
            ];

            try {
                $this->episodeRepository->delete($row, $fsPodcasts);
            } catch (Throwable $e) {
                $result->errors[] = sprintf('%s: %s', $title, $e);
            }
        }

        return $result;
    }

    private function doEdit(
        ServerRequest $request,
        Query $rowsQuery,
        array $records
    ): PodcastBatchResult {
        $result = new PodcastBatchResult();

        $recordsById = array_column($records, null, 'id');

        /** @var ReadWriteBatchIteratorAggregate<array-key, PodcastEpisode> $rows */
        $rows = ReadWriteBatchIteratorAggregate::fromQuery($rowsQuery, self::BATCH_SIZE);

        foreach ($rows as $row) {
            $id = $row->id;
            $title = $row->title;

            $result->episodes[] = [
                'id' => $id,
                'title' => $title,
            ];

            if (isset($recordsById[$id])) {
                try {
                    $record = $this->fromArray($recordsById[$id], $row);

                    $errors = $this->validator->validate($record);
                    if (count($errors) > 0) {
                        throw ValidationException::fromValidationErrors($errors);
                    }

                    $this->em->persist($record);
                } catch (Throwable $e) {
                    $result->errors[] = sprintf('%s: %s', $title, $e);
                }
            } else {
                $result->errors[] = sprintf('%s: No changes supplied.', $title);
            }
        }

        return $result;
    }
}
