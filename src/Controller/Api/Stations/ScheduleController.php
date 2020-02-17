<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;

class ScheduleController extends AbstractStationApiCrudController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em,
        CacheInterface $cache
    ): ResponseInterface {
        $station = $request->getStation();

        $cacheKey = 'api_station_' . $station->getId() . '_schedule_upcoming';

        if ($cache->has($cacheKey)) {
            $response->withJson($cache->get($cacheKey));
        }

        $scheduleItems = $em->createQuery(/** @lang DQL */ 'SELECT
            ssc, sp, sst
            FROM App\Entity\StationSchedule ssc
            LEFT JOIN ssc.playlist sp
            LEFT JOIN ssc.streamer sst
            WHERE (sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1)
            OR (sst.station = :station AND sst.is_active = 1)
        ')->setParameter('station', $station)
            ->execute();

        $tz = new \DateTimeZone($station->getTimezone());

        $startDate = Chronos::now($tz);
        $endDate = $startDate->addDay()->addHour();

        $events = [];

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

                    $row = [
                        'id' => $scheduleItem->getId(),
                        'start_timestamp' => $start->getTimestamp(),
                        'start' => $start->toIso8601String(),
                        'end_timestamp' => $end->getTimestamp(),
                        'end' => $end->toIso8601String(),
                        'is_now' => $start->lessThan($startDate),
                    ];

                    if ($scheduleItem->getPlaylist() instanceof Entity\StationPlaylist) {
                        $playlist = $scheduleItem->getPlaylist();

                        $row['type'] = 'playlist';
                        $row['name'] = $playlist->getName();
                        $row['edit_url'] = (string)$request->getRouter()->named(
                            'api:stations:playlist',
                            ['station_id' => $station->getId(), 'id' => $playlist->getId()]
                        );
                    } elseif ($scheduleItem->getStreamer() instanceof Entity\StationStreamer) {
                        $streamer = $scheduleItem->getStreamer();

                        $row['type'] = 'streamer';
                        $row['name'] = $streamer->getDisplayName();
                        $row['edit_url'] = (string)$request->getRouter()->named(
                            'api:stations:streamer',
                            ['station_id' => $station->getId(), 'id' => $streamer->getId()]
                        );
                    }

                    $events[] = $row;
                }

                $i = $i->addDay();
            }
        }

        usort($events, function ($a, $b) {
            return $a['start_timestamp'] <=> $b['start_timestamp'];
        });

        $events = array_slice($events, 0, 5);

        $cache->set($cacheKey, $events, 60);

        return $response->withJson($events);
    }
}
