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
use App\Utilities\Types;
use Doctrine\ORM\Query;
use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Throwable;

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
                'id' => $row->getIdRequired(),
                'title' => $row->getTitle(),
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
            $id = $row->getIdRequired();
            $title = $row->getTitle();

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
            $id = $row->getIdRequired();
            $title = $row->getTitle();

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
