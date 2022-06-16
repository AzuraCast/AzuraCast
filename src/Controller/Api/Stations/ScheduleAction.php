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
        new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
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
        new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class ScheduleAction
{
    use HasScheduleDisplay;

    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly CacheInterface $cache,
        private readonly Entity\ApiGenerator\ScheduleApiGenerator $scheduleApiGenerator,
        private readonly Entity\Repository\StationScheduleRepository $scheduleRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        string $station_id
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = $station->getTimezoneObject();

        $queryParams = $request->getQueryParams();

        if (isset($queryParams['start'])) {
            $dateRange = $this->getScheduleDateRange($request);

            $cacheKey = 'api_station_' . $station->getId() . '_schedule_'
                . $dateRange->getStart()->format('Ymd') . '-'
                . $dateRange->getEnd()->format('Ymd');

            $events = $this->cache->get(
                $cacheKey,
                function (CacheItem $item) use (
                    $station,
                    $dateRange
                ) {
                    $item->expiresAfter(600);

                    $nowTz = CarbonImmutable::now($station->getTimezoneObject());
                    $events = $this->scheduleRepo->getAllScheduledItemsForStation($station);

                    return $this->getEvents(
                        $dateRange,
                        $nowTz,
                        $this->scheduler,
                        $events,
                        [$this->scheduleApiGenerator, '__invoke']
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

            $events = $this->cache->get(
                $cacheKey,
                function (CacheItem $item) use ($station, $now) {
                    $item->expiresAfter(60);
                    return $this->scheduleRepo->getUpcomingSchedule($station, $now);
                }
            );

            $rows = (int)$request->getQueryParam('rows', 5);
            $events = array_slice($events, 0, $rows);
        }

        return $response->withJson($events);
    }
}
