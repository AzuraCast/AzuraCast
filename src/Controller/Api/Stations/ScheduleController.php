<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity\Repository\StationScheduleRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

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
     * @param EntityManagerInterface $em
     * @param CacheInterface $cache
     * @param StationScheduleRepository $scheduleRepo
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        CacheInterface $cache,
        StationScheduleRepository $scheduleRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = $station->getTimezoneObject();

        $now = $request->getQueryParam('now');
        if (!empty($now)) {
            $now = CarbonImmutable::parse($now, $tz);
            $cacheKey = 'api_station_' . $station->getId() . '_schedule_' . $now->format('Ymd_gia');
        } else {
            $now = CarbonImmutable::now($tz);
            $cacheKey = 'api_station_' . $station->getId() . '_schedule_upcoming';
        }

        $events = $cache->get(
            $cacheKey,
            function (CacheItem $item) use ($scheduleRepo, $station, $now) {
                $item->expiresAfter(60);
                return $scheduleRepo->getUpcomingSchedule($station, $now);
            }
        );

        $rows = (int)$request->getQueryParam('rows', 5);
        $events = array_slice($events, 0, $rows);

        return $response->withJson($events);
    }
}
