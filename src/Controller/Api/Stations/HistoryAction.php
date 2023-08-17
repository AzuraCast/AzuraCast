<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App;
use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Controller\SingleActionInterface;
use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Entity\ApiGenerator\SongHistoryApiGenerator;
use App\Entity\SongHistory;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Carbon\CarbonImmutable;
use Doctrine\ORM\Query;
use League\Csv\Writer;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[
    OA\Get(
        path: '/station/{station_id}/history',
        operationId: 'getStationHistory',
        description: 'Return song playback history items for a given station.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: History'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'start',
                description: 'The start date for records, in YYYY-MM-DD format.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'end',
                description: 'The end date for records, in YYYY-MM-DD format.',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_DetailedSongHistory')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class HistoryAction implements SingleActionInterface
{
    use AcceptsDateRange;
    use EntityManagerAwareTrait;
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly SongHistoryApiGenerator $songHistoryApiGenerator
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        set_time_limit($this->environment->getSyncLongExecutionTime());

        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $dateRange = $this->getDateRange($request, $stationTz);
        $start = $dateRange->getStart();
        $end = $dateRange->getEnd();

        $qb = $this->em->createQueryBuilder();

        $qb->select('sh, sr, sp, ss')
            ->from(SongHistory::class, 'sh')
            ->leftJoin('sh.request', 'sr')
            ->leftJoin('sh.playlist', 'sp')
            ->leftJoin('sh.streamer', 'ss')
            ->where('sh.station_id = :station_id')
            ->andWhere('sh.timestamp_start >= :start AND sh.timestamp_start <= :end')
            ->andWhere('sh.listeners_start IS NOT NULL')
            ->setParameter('station_id', $station->getId())
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $format = $request->getQueryParam('format', 'json');

        if ('csv' === $format) {
            $csvFilename = sprintf(
                '%s_timeline_%s_to_%s.csv',
                $station->getShortName(),
                $start->format('Y-m-d_H-i-s'),
                $end->format('Y-m-d_H-i-s')
            );

            return $this->exportReportAsCsv(
                $response,
                $station,
                $qb->getQuery(),
                $csvFilename
            );
        }

        $searchPhrase = trim($request->getQueryParam('searchPhrase') ?? '');
        if (!empty($searchPhrase)) {
            $qb->andWhere('(sh.title LIKE :query OR sh.artist LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

        $qb->orderBy('sh.timestamp_start', 'DESC');

        $paginator = App\Paginator::fromQueryBuilder($qb, $request);

        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function ($shRow) use ($router) {
                /** @var SongHistory $shRow */
                $row = $this->songHistoryApiGenerator->detailed($shRow);
                $row->resolveUrls($router->getBaseUrl());

                return $row;
            }
        );

        return $paginator->write($response);
    }

    private function exportReportAsCsv(
        Response $response,
        Station $station,
        Query $query,
        string $filename
    ): ResponseInterface {
        if (!($tempFile = tmpfile())) {
            throw new RuntimeException('Could not create temp file.');
        }
        $csv = Writer::createFromStream($tempFile);

        $csv->insertOne([
            'Date',
            'Time',
            'Listeners',
            'Delta',
            'Track',
            'Artist',
            'Playlist',
            'Streamer',
        ]);

        /** @var SongHistory $sh */
        foreach (ReadOnlyBatchIteratorAggregate::fromQuery($query, 100) as $sh) {
            $datetime = CarbonImmutable::createFromTimestamp(
                $sh->getTimestampStart(),
                $station->getTimezoneObject()
            );

            $playlist = $sh->getPlaylist();
            $playlistName = (null !== $playlist)
                ? $playlist->getName()
                : '';

            $streamer = $sh->getStreamer();
            $streamerName = (null !== $streamer)
                ? $streamer->getDisplayName()
                : '';

            $csv->insertOne([
                $datetime->format('Y-m-d'),
                $datetime->format('g:ia'),
                $sh->getListenersStart(),
                $sh->getDeltaTotal(),
                $sh->getTitle() ?: $sh->getText(),
                $sh->getArtist(),
                $playlistName,
                $streamerName,
            ]);
        }

        return $response->withFileDownload($tempFile, $filename, 'text/csv');
    }
}
