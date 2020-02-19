<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class ScheduleController extends AbstractStationApiCrudController
{
    /**
     * @OA\Get(path="/station/{station_id}/schedule",
     *   tags={"Stations: Schedules"},
     *   description="Return upcoming and currently ongoing schedule entries.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Parameter(
     *     name="now",
     *     description="The date/time to compare schedule items to. Defaults to the current date and time.",
     *     in="query",
     *     required=false,
     *     @OA\Schema(type="string")
     *   ),
     *   @OA\Parameter(
     *     name="rows",
     *     description="The number of upcoming/ongoing schedule entries to return. Defaults to 5.",
     *     in="query",
     *     required=false,
     *     @OA\Schema(type="integer")
     *   ),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_StationSchedule"))
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied")
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param EntityManager $em
     * @param CacheInterface $cache
     *
     * @return ResponseInterface
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em,
        CacheInterface $cache
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = new \DateTimeZone($station->getTimezone());

        $now = $request->getQueryParam('now');
        if (!empty($now)) {
            $now = Chronos::parse($now, $tz);
            $cacheKey = 'api_station_' . $station->getId() . '_schedule_' . $now->format('Ymd_gia');
        } else {
            $now = Chronos::now($tz);
            $cacheKey = 'api_station_' . $station->getId() . '_schedule_upcoming';
        }

        if ($cache->has($cacheKey)) {
            $events = $cache->get($cacheKey);
        } else {
            $startDate = $now->subDay();
            $endDate = $now->addDay()->addHour();

            $events = [];

            $scheduleItems = $em->createQuery(/** @lang DQL */ 'SELECT
                ssc, sp, sst
                FROM App\Entity\StationSchedule ssc
                LEFT JOIN ssc.playlist sp
                LEFT JOIN ssc.streamer sst
                WHERE (sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1)
                OR (sst.station = :station AND sst.is_active = 1)
            ')->setParameter('station', $station)
                ->execute();

            foreach ($scheduleItems as $scheduleItem) {
                /** @var Entity\StationSchedule $scheduleItem */
                $i = $startDate;

                while ($i <= $endDate) {
                    $dayOfWeek = $i->format('N');

                    if ($scheduleItem->shouldPlayOnCurrentDate($i)
                        && $scheduleItem->isScheduledToPlayToday($dayOfWeek)) {
                        $start = Entity\StationSchedule::getDateTime($scheduleItem->getStartTime(), $i);
                        $end = Entity\StationSchedule::getDateTime($scheduleItem->getEndTime(), $i);

                        // Handle overnight schedule items
                        if ($end < $start) {
                            $end = $end->addDay();
                        }

                        // Skip events that have already happened today.
                        if ($end->lessThan($now)) {
                            $i = $i->addDay();
                            continue;
                        }

                        $row = new Entity\Api\StationSchedule;
                        $row->id = $scheduleItem->getId();
                        $row->start_timestamp = $start->getTimestamp();
                        $row->start = $start->toIso8601String();
                        $row->end_timestamp = $end->getTimestamp();
                        $row->end = $end->toIso8601String();
                        $row->is_now = $start->lessThanOrEquals($startDate);

                        if ($scheduleItem->getPlaylist() instanceof Entity\StationPlaylist) {
                            $playlist = $scheduleItem->getPlaylist();

                            $row->type = Entity\Api\StationSchedule::TYPE_PLAYLIST;
                            $row->name = $playlist->getName();
                        } elseif ($scheduleItem->getStreamer() instanceof Entity\StationStreamer) {
                            $streamer = $scheduleItem->getStreamer();

                            $row->type = Entity\Api\StationSchedule::TYPE_STREAMER;
                            $row->name = $streamer->getDisplayName();
                        }

                        $events[] = $row;
                    }

                    $i = $i->addDay();
                }
            }

            usort($events, function ($a, $b) {
                return $a->start_timestamp <=> $b->start_timestamp;
            });

            $cache->set($cacheKey, $events, 60);
        }

        $rows = $request->getQueryParam('rows', 5);
        $events = array_slice($events, 0, $rows);

        return $response->withJson($events);
    }
}
