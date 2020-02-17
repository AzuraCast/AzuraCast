<?php
namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Cake\Chronos\Chronos;
use Doctrine\ORM\EntityManager;
use Psr\Http\Message\ResponseInterface;

class ScheduleController extends AbstractStationApiCrudController
{
    use Traits\CalendarView;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManager $em
    ): ResponseInterface {
        $station = $request->getStation();
        $scheduleItems = $em->createQuery(/** @lang DQL */ 'SELECT
            ssc, sp, sst
            FROM App\Entity\StationSchedule ssc
            LEFT JOIN ssc.playlist sp
            LEFT JOIN ssc.streamer sst
            WHERE (sp.station = :station AND sp.is_jingle = 0 AND sp.is_enabled = 1)
            OR (sst.station = :station AND sst.is_active = 1)
        ')->setParameter('station', $station)
            ->execute();

        return $this->renderEvents(
            $request,
            $response,
            $scheduleItems,
            function (Entity\StationSchedule $scheduleItem, Chronos $start, Chronos $end) use ($request, $station) {
                $row = [
                    'id' => $scheduleItem->getId(),
                    'start' => $start->toIso8601String(),
                    'end' => $end->toIso8601String(),
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
                } else {
                    return null;
                }

                return $row;
            }
        );
    }
}
