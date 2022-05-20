<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\CsvWriterTempFile;
use App\Service\DeviceDetector;
use App\Service\IpGeolocation;
use Carbon\CarbonImmutable;
use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

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
class ListenersAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\ListenerRepository $listenerRepo,
        Entity\Repository\StationMountRepository $mountRepo,
        Entity\Repository\StationRemoteRepository $remoteRepo,
        IpGeolocation $geoLite,
        DeviceDetector $deviceDetector,
        Environment $environment
    ): ResponseInterface {
        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $params = $request->getQueryParams();

        $isLive = empty($params['start']);
        $now = CarbonImmutable::now($stationTz);

        if ($isLive) {
            $range = 'live';
            $startTimestamp = $now->getTimestamp();
            $endTimestamp = $now->getTimestamp();

            $listenersIterator = $listenerRepo->iterateLiveListenersArray($station);
        } else {
            $start = CarbonImmutable::parse($params['start'], $stationTz)
                ->setSecond(0);
            $startTimestamp = $start->getTimestamp();

            $end = CarbonImmutable::parse($params['end'] ?? $params['start'], $stationTz)
                ->setSecond(59);
            $endTimestamp = $end->getTimestamp();

            $range = $start->format('Y-m-d_H-i-s') . '_to_' . $end->format('Y-m-d_H-i-s');

            $listenersIterator = $em->createQuery(
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

        $mountNames = $mountRepo->getDisplayNames($station);
        $remoteNames = $remoteRepo->getDisplayNames($station);

        /** @var Entity\Api\Listener[] $listeners */
        $listeners = [];
        $listenersByHash = [];

        $groupByUnique = ('false' !== ($params['unique'] ?? 'true'));
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

            $api = Entity\Api\Listener::fromArray($listener);

            if (null !== $listener['mount_id']) {
                $api->mount_is_local = true;
                $api->mount_name = $mountNames[$listener['mount_id']];
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

                /** @var Entity\Api\Listener $api */
                $api = $listenerInfo['api'];
                $api->connected_on = $startTime;
                $api->connected_until = $endTime;
                $api->connected_time = Entity\Listener::getListenerSeconds($intervals);

                $listeners[] = $api;
            }
        }

        $format = $params['format'] ?? 'json';

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
     * @param Entity\Station $station
     * @param Entity\Api\Listener[] $listeners
     * @param string $filename
     */
    protected function exportReportAsCsv(
        Response $response,
        Entity\Station $station,
        array $listeners,
        string $filename
    ): ResponseInterface {
        $tempFile = new CsvWriterTempFile();
        $csv = $tempFile->getWriter();

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

        return $response->withFileDownload($tempFile->getTempPath(), $filename, 'text/csv');
    }
}
