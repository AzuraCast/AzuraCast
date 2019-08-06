<?php
namespace App\Controller\Api\Stations;

use App;
use App\Entity;
use App\Http\RequestHelper;
use Azura\Doctrine\Paginator;
use Azura\Utilities\Csv;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class HistoryController
{
    /** @var EntityManager */
    protected $em;

    /** @var App\ApiUtilities */
    protected $api_utils;

    /**
     * @param EntityManager $em
     * @param App\ApiUtilities $api_utils
     *
     * @see App\Controller\Api\ApiProvider
     */
    public function __construct(EntityManager $em, App\ApiUtilities $api_utils)
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int|string $station_id
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response, $station_id): ResponseInterface
    {
        $station = RequestHelper::getStation($request);
        $station_tz = new \DateTimeZone($station->getTimezone());

        $params = $request->getQueryParams();
        if (!empty($params['start'])) {
            $start = Chronos::parse($params['start']. ' 00:00:00', $station_tz);
            $end = Chronos::parse(($params['end'] ?? $params['start']) . ' 23:59:59', $station_tz);
        } else {
            $start = Chronos::parse('-2 weeks', $station_tz);
            $end = Chronos::now($station_tz);
        }

        $qb = $this->em->createQueryBuilder();

        $qb->select('sh, sr, sp, s')
            ->from(Entity\SongHistory::class, 'sh')
            ->leftJoin('sh.request', 'sr')
            ->leftJoin('sh.playlist', 'sp')
            ->leftJoin('sh.song', 's')
            ->where('sh.station_id = :station_id')
            ->andWhere('sh.timestamp_start >= :start AND sh.timestamp_start <= :end')
            ->andWhere('sh.listeners_start IS NOT NULL')
            ->setParameter('station_id', $station_id)
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
                'Playlist'
            ];

            foreach ($qb->getQuery()->getArrayResult() as $song_row) {
                $datetime = Chronos::createFromTimestamp($song_row['timestamp_start'], $station_tz);
                $export_row = [
                    $datetime->format('Y-m-d'),
                    $datetime->format('g:ia'),
                    $song_row['listeners_start'],
                    $song_row['delta_total'],
                    $song_row['song']['title'] ?: $song_row['song']['text'],
                    $song_row['song']['artist'],
                    $song_row['playlist']['name'] ?? '',
                ];

                $export_all[] = $export_row;
            }

            $csv_file = Csv::arrayToCsv($export_all);
            $csv_filename = $station->getShortName() . '_timeline_' . $start->format('Ymd') . '_to_' . $end->format('Ymd') . '.csv';

            return App\Http\ResponseHelper::renderStringAsFile($response, $csv_file, 'text/csv', $csv_filename);
        }

        $search_phrase = trim($params['searchPhrase']);
        if (!empty($search_phrase)) {
            $qb->andWhere('(s.title LIKE :query OR s.artist LIKE :query)')
                ->setParameter('query', '%'.$search_phrase.'%');
        }

        $qb->orderBy('sh.timestamp_start', 'DESC');

        $paginator = new Paginator($qb);
        $paginator->setFromRequest($request);

        $is_bootgrid = $paginator->isFromBootgrid();
        $router = RequestHelper::getRouter($request);

        $paginator->setPostprocessor(function($sh_row) use ($is_bootgrid, $router) {

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
