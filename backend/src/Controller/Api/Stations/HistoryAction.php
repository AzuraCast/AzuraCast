<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Container\EnvironmentAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Controller\SingleActionInterface;
use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Entity\Api\DetailedSongHistory;
use App\Entity\ApiGenerator\SongHistoryApiGenerator;
use App\Entity\SongHistory;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Paginator;
use App\Utilities\Types;
use Doctrine\ORM\Query;
use League\Csv\Writer;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[
    OA\Get(
        path: '/station/{station_id}/history',
        operationId: 'getStationHistory',
        summary: 'Return song playback history items for a given station.',
        tags: [OpenApi::TAG_STATIONS_REPORTS],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
            new OA\Parameter(
                name: 'start',
                description: 'The start date for records, in PHP-supported date/time format.'
                . ' (https://www.php.net/manual/en/datetime.formats.php)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
            new OA\Parameter(
                name: 'end',
                description: 'The end date for records, in PHP-supported date/time format.'
                . ' (https://www.php.net/manual/en/datetime.formats.php)',
                in: 'query',
                required: false,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: DetailedSongHistory::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
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
        $start = $dateRange->start;
        $end = $dateRange->end;

        $qb = $this->em->createQueryBuilder();

        $qb->select('sh, sr, sp, ss')
            ->from(SongHistory::class, 'sh')
            ->leftJoin('sh.request', 'sr')
            ->leftJoin('sh.playlist', 'sp')
            ->leftJoin('sh.streamer', 'ss')
            ->where('sh.station = :station')
            ->andWhere('sh.timestamp_start >= :start AND sh.timestamp_start <= :end')
            ->andWhere('sh.listeners_start IS NOT NULL')
            ->setParameter('station', $station)
            ->setParameter('start', $start)
            ->setParameter('end', $end);

        $format = $request->getQueryParam('format', 'json');

        if ('csv' === $format) {
            $csvFilename = sprintf(
                '%s_timeline_%s_to_%s.csv',
                $station->short_name,
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

        $searchPhrase = Types::stringOrNull($request->getQueryParam('searchPhrase'), true);
        if (null !== $searchPhrase) {
            $qb->andWhere('(sh.title LIKE :query OR sh.artist LIKE :query)')
                ->setParameter('query', '%' . $searchPhrase . '%');
        }

        $qb->orderBy('sh.timestamp_start', 'DESC');

        $paginator = Paginator::fromQueryBuilder($qb, $request);

        $paginator->setPostprocessor(
            fn(SongHistory $shRow) => $this->songHistoryApiGenerator->detailed($shRow)
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

        $stationTz = $station->getTimezoneObject();

        /** @var SongHistory $sh */
        foreach (ReadOnlyBatchIteratorAggregate::fromQuery($query, 100) as $sh) {
            $datetime = $sh->timestamp_start->setTimezone($stationTz);

            $playlist = $sh->playlist;
            $playlistName = (null !== $playlist)
                ? $playlist->name
                : '';

            $streamer = $sh->streamer;
            $streamerName = (null !== $streamer)
                ? $streamer->display_name
                : '';

            $csv->insertOne([
                $datetime->format('Y-m-d'),
                $datetime->format('g:ia'),
                $sh->listeners_start,
                $sh->delta_total,
                $sh->title ?: $sh->text,
                $sh->artist,
                $playlistName,
                $streamerName,
            ]);
        }

        return $response->withFileDownload($tempFile, $filename, 'text/csv');
    }
}
