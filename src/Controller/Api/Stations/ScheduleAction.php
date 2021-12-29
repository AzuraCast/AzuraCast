<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasScheduleDisplay;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\AutoDJ\Scheduler;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Cache\CacheItem;
use Symfony\Contracts\Cache\CacheInterface;

#[OA\Get(
    path: '/station/{station_id}/schedule',
    operationId: 'getSchedule',
    description: 'Return upcoming and currently ongoing schedule entries.',
    tags: ['Stations: Schedules'],
    parameters: [
        new OA\Parameter(ref: OpenApi::STATION_ID_REQUIRED),
        new OA\Parameter(
            name: 'now',
            description: 'The date/time to compare schedule items to. Defaults to the current date and time.',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'string')
        ),
        new OA\Parameter(
            name: 'rows',
            description: 'The number of upcoming/ongoing schedule entries to return. Defaults to 5.',
            in: 'query',
            required: false,
            schema: new OA\Schema(type: 'integer')
        ),
    ],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: '#/components/schemas/Api_StationSchedule')
            )
        ),
        new OA\Response(
            response: 404,
            description: 'Station not found'
        ),
        new OA\Response(
            response: 403,
            description: 'Access denied'
        ),
    ]
)]
class ScheduleAction
{
    use HasScheduleDisplay;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Scheduler $scheduler,
        CacheInterface $cache,
        Entity\ApiGenerator\ScheduleApiGenerator $scheduleApiGenerator,
        Entity\Repository\StationScheduleRepository $scheduleRepo
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = $station->getTimezoneObject();

        $queryParams = $request->getQueryParams();

        if (isset($queryParams['start'])) {
            [$startDate, $endDate] = $this->getDateRange($request);

            $cacheKey = 'api_station_' . $station->getId() . '_schedule_'
                . $startDate->format('Ymd') . '-'
                . $endDate->format('Ymd');

            $events = $cache->get(
                $cacheKey,
                function (CacheItem $item) use (
                    $station,
                    $scheduleRepo,
                    $scheduleApiGenerator,
                    $scheduler,
                    $startDate,
                    $endDate
                ) {
                    $item->expiresAfter(600);

                    $nowTz = CarbonImmutable::now($station->getTimezoneObject());
                    $events = $scheduleRepo->getAllScheduledItemsForStation($station);

                    return $this->getEvents(
                        $startDate,
                        $endDate,
                        $nowTz,
                        $scheduler,
                        $events,
                        [$scheduleApiGenerator, '__invoke']
                    );
                }
            );
        } else {
            if (!empty($queryParams['now'])) {
                $now = CarbonImmutable::parse($queryParams['now'], $tz);
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
        }

        return $response->withJson($events);
    }
}
