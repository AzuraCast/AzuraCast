<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Controller\Api\Traits\HasScheduleDisplay;
use App\Controller\SingleActionInterface;
use App\Entity\Api\StationSchedule;
use App\Entity\ApiGenerator\ScheduleApiGenerator;
use App\Entity\Repository\StationScheduleRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\AutoDJ\Scheduler;
use App\Utilities\Types;
use Carbon\CarbonImmutable;
use OpenApi\Attributes as OA;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/station/{station_id}/schedule',
    operationId: 'getSchedule',
    summary: 'Return upcoming and currently ongoing schedule entries.',
    security: [],
    tags: [OpenApi::TAG_PUBLIC_STATIONS],
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
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: StationSchedule::class)
            )
        ),
        new OpenApi\Response\NotFound(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class ScheduleAction implements SingleActionInterface
{
    use HasScheduleDisplay;

    public function __construct(
        private readonly Scheduler $scheduler,
        private readonly ScheduleApiGenerator $scheduleApiGenerator,
        private readonly StationScheduleRepository $scheduleRepo,
        private readonly CacheItemPoolInterface $psr6Cache
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $tz = $station->getTimezoneObject();

        $queryParams = $request->getQueryParams();

        if (isset($queryParams['start'])) {
            $dateRange = $this->getDateRange($request, $tz);

            $cacheKey = 'api_station_' . $station->id . '_schedule_'
                . $dateRange->format('Ymd', '-');

            $cacheItem = $this->psr6Cache->getItem(urlencode($cacheKey));

            if (!$cacheItem->isHit()) {
                $events = $this->scheduleRepo->getAllScheduledItemsForStation($station);

                $cacheItem->set(
                    $this->getEvents(
                        $station,
                        $dateRange,
                        $this->scheduler,
                        $events,
                        [$this->scheduleApiGenerator, '__invoke']
                    )
                );
                $cacheItem->expiresAfter(600);

                $this->psr6Cache->save($cacheItem);
            }

            $events = $cacheItem->get();
        } else {
            if (!empty($queryParams['now'])) {
                $now = CarbonImmutable::parse($queryParams['now'], $tz)
                    ->setTimezone($tz);

                $cacheKey = 'api_station_' . $station->id . '_schedule_' . $now->format('Ymd_gia');
            } else {
                $now = CarbonImmutable::now($tz);
                $cacheKey = 'api_station_' . $station->id . '_schedule_upcoming';
            }

            $cacheItem = $this->psr6Cache->getItem(urlencode($cacheKey));

            if (!$cacheItem->isHit()) {
                $cacheItem->set($this->scheduleRepo->getUpcomingSchedule($station, $now));
                $cacheItem->expiresAfter(60);

                $this->psr6Cache->save($cacheItem);
            }

            $events = $cacheItem->get();

            $rows = Types::int($request->getQueryParam('rows'), 5);
            $events = array_slice($events, 0, $rows);
        }

        return $response->withJson($events);
    }
}
