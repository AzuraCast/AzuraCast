<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App;
use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Utilities\Csv;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class HistoryController
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected Entity\ApiGenerator\SongHistoryApiGenerator $songHistoryApiGenerator
    ) {
    }

    /**
     * @OA\Get(path="/station/{station_id}/history",
     *   tags={"Stations: History"},
     *   description="Return song playback history items for a given station.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="start",
     *     description="The start date for records, in YYYY-MM-DD format.",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Parameter(
     *     name="end",
     *     description="The end date for records, in YYYY-MM-DD format.",
     *     in="query",
     *     required=false,
     *     @OA\Schema(
     *         type="string"
     *     )
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_DetailedSongHistory"))
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        $params = $request->getQueryParams();
        if (!empty($params['start'])) {
            $start = CarbonImmutable::parse($params['start'] . ' 00:00:00', $station_tz);
            $end = CarbonImmutable::parse(($params['end'] ?? $params['start']) . ' 23:59:59', $station_tz);
        } else {
            $start = CarbonImmutable::parse('-2 weeks', $station_tz);
            $end = CarbonImmutable::now($station_tz);
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('sh, sr, sp, ss')
            ->from(Entity\SongHistory::class, 'sh')
            ->leftJoin('sh.request', 'sr')
            ->leftJoin('sh.playlist', 'sp')
            ->leftJoin('sh.streamer', 'ss')
            ->where('sh.station_id = :station_id')
            ->andWhere('sh.timestamp_start >= :start AND sh.timestamp_start <= :end')
            ->andWhere('sh.listeners_start IS NOT NULL')
            ->setParameter('station_id', $station->getId())
            ->setParameter('start', $start->getTimestamp())
            ->setParameter('end', $end->getTimestamp());

        $format = $params['format'] ?? 'json';

        if ('csv' === $format) {
            $export_all = [];
            $export_all[] = [
                'Date',
                'Time',
                'Listeners',
                'Delta',
                'Track',
                'Artist',
                'Playlist',
                'Streamer',
            ];

            foreach (ReadOnlyBatchIteratorAggregate::fromQuery($qb->getQuery(), 100) as $sh) {
                /** @var Entity\SongHistory $sh */
                $datetime = CarbonImmutable::createFromTimestamp($sh->getTimestampStart(), $station_tz);

                $playlist = $sh->getPlaylist();
                $playlistName = (null !== $playlist)
                    ? $playlist->getName()
                    : '';

                $streamer = $sh->getStreamer();
                $streamerName = (null !== $streamer)
                    ? $streamer->getDisplayName()
                    : '';

                $export_row = [
                    $datetime->format('Y-m-d'),
                    $datetime->format('g:ia'),
                    $sh->getListenersStart(),
                    $sh->getDeltaTotal(),
                    $sh->getTitle() ?: $sh->getText(),
                    $sh->getArtist(),
                    $playlistName,
                    $streamerName,
                ];

                $export_all[] = $export_row;
            }

            $csv_file = Csv::arrayToCsv($export_all);
            $csv_filename = sprintf(
                '%s_timeline_%s_to_%s.csv',
                $station->getShortName(),
                $start->format('Ymd'),
                $end->format('Ymd')
            );

            return $response->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
        }

        $search_phrase = trim($params['searchPhrase']);
        if (!empty($search_phrase)) {
            $qb->andWhere('(sh.title LIKE :query OR sh.artist LIKE :query)')
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $qb->orderBy('sh.timestamp_start', 'DESC');

        $paginator = App\Paginator::fromQueryBuilder($qb, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(
            function ($sh_row) use ($is_bootgrid, $router) {
                /** @var Entity\SongHistory $sh_row */
                $row = $this->songHistoryApiGenerator->detailed($sh_row);
                $row->resolveUrls($router->getBaseUrl());

                if ($is_bootgrid) {
                    return App\Utilities\Arrays::flattenArray($row, '_');
                }

                return $row;
            }
        );

        return $paginator->write($response);
    }
}
