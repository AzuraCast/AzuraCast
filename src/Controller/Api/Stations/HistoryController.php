<?php
namespace App\Controller\Api\Stations;

use App;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Paginator\QueryPaginator;
use App\Utilities\Csv;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class HistoryController
{
    protected EntityManagerInterface $em;

    protected App\ApiUtilities $api_utils;

    public function __construct(EntityManagerInterface $em, App\ApiUtilities $api_utils)
    {
        $this->em = $em;
        $this->api_utils = $api_utils;
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
     *
     * @return ResponseInterface
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

            foreach ($qb->getQuery()->getArrayResult() as $song_row) {
                $datetime = CarbonImmutable::createFromTimestamp($song_row['timestamp_start'], $station_tz);
                $export_row = [
                    $datetime->format('Y-m-d'),
                    $datetime->format('g:ia'),
                    $song_row['listeners_start'],
                    $song_row['delta_total'],
                    $song_row['title'] ?: $song_row['text'],
                    $song_row['artist'],
                    $song_row['playlist']['name'] ?? '',
                    $song_row['streamer']['display_name'] ?? $song_row['streamer']['streamer_username'] ?? '',
                ];

                $export_all[] = $export_row;
            }

            $csv_file = Csv::arrayToCsv($export_all);
            $csv_filename = $station->getShortName() . '_timeline_' . $start->format('Ymd') . '_to_' . $end->format('Ymd') . '.csv';

            return $response->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
        }

        $search_phrase = trim($params['searchPhrase']);
        if (!empty($search_phrase)) {
            $qb->andWhere('(sh.title LIKE :query OR sh.artist LIKE :query)')
                ->setParameter('query', '%' . $search_phrase . '%');
        }

        $qb->orderBy('sh.timestamp_start', 'DESC');

        $paginator = new QueryPaginator($qb, $request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = $request->getRouter();

        $paginator->setPostprocessor(function ($sh_row) use ($is_bootgrid, $router) {

            /** @var Entity\SongHistory $sh_row */
            $row = $sh_row->api(new Entity\Api\DetailedSongHistory, $this->api_utils);
            $row->resolveUrls($router->getBaseUrl());

            if ($is_bootgrid) {
                return App\Utilities::flattenArray($row, '_');
            }

            return $row;
        });

        return $paginator->write($response);
    }
}
