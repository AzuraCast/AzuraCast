<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Traits\AcceptsDateRange;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Listener as ApiListener;
use App\Entity\Listener;
use App\Entity\Repository\ListenerRepository;
use App\Entity\Repository\StationHlsStreamRepository;
use App\Entity\Repository\StationMountRepository;
use App\Entity\Repository\StationRemoteRepository;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use Carbon\CarbonImmutable;
use Doctrine\ORM\AbstractQuery;
use League\Csv\Writer;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

#[
    OA\Get(
        path: '/station/{station_id}/listeners',
        operationId: 'getStationListeners',
        description: 'Return detailed information about current listeners.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Stations: Listeners'],
        parameters: [
            new OA\Parameter(ref: OpenApi::REF_STATION_ID_REQUIRED),
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Api_Listener')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class ListenersAction implements SingleActionInterface
{
    use AcceptsDateRange;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly ListenerRepository $listenerRepo,
        private readonly StationMountRepository $mountRepo,
        private readonly StationRemoteRepository $remoteRepo,
        private readonly StationHlsStreamRepository $hlsStreamRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $queryParams = $request->getQueryParams();

        $isLive = empty($queryParams['start']);
        $now = CarbonImmutable::now($stationTz);

        if ($isLive) {
            $range = 'live';
            $startTimestamp = $now->getTimestamp();
            $endTimestamp = $now->getTimestamp();

            $listenersIterator = $this->listenerRepo->iterateLiveListenersArray($station);
        } else {
            $dateRange = $this->getDateRange($request, $stationTz);

            $start = $dateRange->getStart();
            $startTimestamp = $start->getTimestamp();

            $end = $dateRange->getEnd();
            $endTimestamp = $end->getTimestamp();

            $range = $start->format('Y-m-d_H-i-s') . '_to_' . $end->format('Y-m-d_H-i-s');

            $listenersIterator = $this->em->createQuery(
                <<<'DQL'
                    SELECT l
                    FROM App\Entity\Listener l
                    WHERE l.station = :station
                    AND l.timestamp_start < :time_end
                    AND (l.timestamp_end = 0 OR l.timestamp_end > :time_start)
                    ORDER BY l.timestamp_start ASC
                DQL
            )->setParameter('station', $station)
                ->setParameter('time_start', $startTimestamp)
                ->setParameter('time_end', $endTimestamp)
                ->toIterable([], AbstractQuery::HYDRATE_ARRAY);
        }

        $mountNames = $this->mountRepo->getDisplayNames($station);
        $remoteNames = $this->remoteRepo->getDisplayNames($station);
        $hlsStreamNames = $this->hlsStreamRepo->getDisplayNames($station);

        /** @var ApiListener[] $listeners */
        $listeners = [];
        $listenersByHash = [];

        $groupByUnique = ('false' !== ($queryParams['unique'] ?? 'true'));
        $nowTimestamp = $now->getTimestamp();

        foreach ($listenersIterator as $listener) {
            $listenerStart = $listener['timestamp_start'];

            if ($isLive) {
                $listenerEnd = $nowTimestamp;
            } else {
                if ($listenerStart < $startTimestamp) {
                    $listenerStart = $startTimestamp;
                }

                $listenerEnd = $listener['timestamp_end'];
                if (0 === $listenerEnd || $listenerEnd > $endTimestamp) {
                    $listenerEnd = $endTimestamp;
                }
            }

            $hash = $listener['listener_hash'];
            if ($groupByUnique && isset($listenersByHash[$hash])) {
                $listenersByHash[$hash]['intervals'][] = [
                    'start' => $listenerStart,
                    'end' => $listenerEnd,
                ];
                continue;
            }

            $api = ApiListener::fromArray($listener);

            if (null !== $listener['mount_id']) {
                $api->mount_is_local = true;
                $api->mount_name = $mountNames[$listener['mount_id']];
            } elseif (null !== $listener['hls_stream_id']) {
                $api->mount_is_local = true;
                $api->mount_name = $hlsStreamNames[$listener['hls_stream_id']];
            } elseif (null !== $listener['remote_id']) {
                $api->mount_is_local = false;
                $api->mount_name = $remoteNames[$listener['remote_id']];
            }

            if ($groupByUnique) {
                $listenersByHash[$hash] = [
                    'api' => $api,
                    'intervals' => [
                        [
                            'start' => $listenerStart,
                            'end' => $listenerEnd,
                        ],
                    ],
                ];
            } else {
                $api->connected_on = $listenerStart;
                $api->connected_until = $listenerEnd;
                $api->connected_time = $listenerEnd - $listenerStart;
                $listeners[] = $api;
            }
        }

        if ($groupByUnique) {
            foreach ($listenersByHash as $listenerInfo) {
                $intervals = (array)$listenerInfo['intervals'];

                $startTime = $nowTimestamp;
                $endTime = 0;
                foreach ($intervals as $interval) {
                    $startTime = min($interval['start'], $startTime);
                    $endTime = max($interval['end'], $endTime);
                }

                /** @var ApiListener $api */
                $api = $listenerInfo['api'];
                $api->connected_on = $startTime;
                $api->connected_until = $endTime;
                $api->connected_time = Listener::getListenerSeconds($intervals);

                $listeners[] = $api;
            }
        }

        $format = $queryParams['format'] ?? 'json';

        if ('csv' === $format) {
            return $this->exportReportAsCsv(
                $response,
                $station,
                $listeners,
                $station->getShortName() . '_listeners_' . $range . '.csv'
            );
        }

        return $response->withJson($listeners);
    }

    /**
     * @param Response $response
     * @param Station $station
     * @param ApiListener[] $listeners
     * @param string $filename
     */
    private function exportReportAsCsv(
        Response $response,
        Station $station,
        array $listeners,
        string $filename
    ): ResponseInterface {
        if (!($tempFile = tmpfile())) {
            throw new RuntimeException('Could not create temp file.');
        }
        $csv = Writer::createFromStream($tempFile);

        $tz = $station->getTimezoneObject();

        $csv->insertOne(
            [
                'IP',
                'Start Time',
                'End Time',
                'Seconds Connected',
                'User Agent',
                'Mount Type',
                'Mount Name',
                'Device: Client',
                'Device: Is Mobile',
                'Device: Is Browser',
                'Device: Is Bot',
                'Device: Browser Family',
                'Device: OS Family',
                'Location: Description',
                'Location: Country',
                'Location: Region',
                'Location: City',
                'Location: Latitude',
                'Location: Longitude',
            ]
        );

        foreach ($listeners as $listener) {
            $startTime = CarbonImmutable::createFromTimestamp($listener->connected_on, $tz);
            $endTime = CarbonImmutable::createFromTimestamp($listener->connected_until, $tz);

            $exportRow = [
                $listener->ip,
                $startTime->toIso8601String(),
                $endTime->toIso8601String(),
                $listener->connected_time,
                $listener->user_agent,
                ($listener->mount_is_local) ? 'Local' : 'Remote',
                $listener->mount_name,
                $listener->device['client'],
                $listener->device['is_mobile'] ? 'True' : 'False',
                $listener->device['is_browser'] ? 'True' : 'False',
                $listener->device['is_bot'] ? 'True' : 'False',
                $listener->device['browser_family'],
                $listener->device['os_family'],
                $listener->location['description'],
                $listener->location['country'],
                $listener->location['region'],
                $listener->location['city'],
                $listener->location['lat'],
                $listener->location['lon'],
            ];

            $csv->insertOne($exportRow);
        }

        return $response->withFileDownload($tempFile, $filename, 'text/csv');
    }
}
