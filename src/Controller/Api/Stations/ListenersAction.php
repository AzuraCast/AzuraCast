<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations;

use App\Doctrine\ReadOnlyBatchIteratorAggregate;
use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Locale;
use App\Service\DeviceDetector;
use App\Service\IpGeolocation;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Psr7\Stream;
use League\Csv\Writer;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class ListenersAction
{
    /**
     * @OA\Get(path="/station/{station_id}/listeners",
     *   tags={"Stations: Listeners"},
     *   description="Return detailed information about current listeners.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Api_Listener"))
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\StationMountRepository $mountRepo,
        Entity\Repository\StationRemoteRepository $remoteRepo,
        IpGeolocation $geoLite,
        DeviceDetector $deviceDetector,
        Environment $environment
    ): ResponseInterface {
        set_time_limit($environment->getSyncLongExecutionTime());

        $station = $request->getStation();
        $stationTz = $station->getTimezoneObject();

        $params = $request->getQueryParams();

        $isLive = empty($params['start']);
        $now = CarbonImmutable::now($stationTz);

        $qb = $em->createQueryBuilder()
            ->select('l')
            ->from(Entity\Listener::class, 'l')
            ->where('l.station = :station')
            ->setParameter('station', $station)
            ->orderBy('l.timestamp_start', 'ASC');

        if ($isLive) {
            $range = 'live';
            $startTimestamp = $now->getTimestamp();
            $endTimestamp = $now->getTimestamp();

            $qb = $qb->andWhere('l.timestamp_end = 0');
        } else {
            $startString = $params['start'];
            if (10 === strlen($startString)) {
                $startString .= ' 00:00:00';
            }

            $start = CarbonImmutable::parse($startString, $stationTz);
            $startTimestamp = $start->getTimestamp();

            $endString = $params['end'] ?? $params['start'];
            if (10 === strlen($endString)) {
                $endString .= ' 23:59:59';
            }

            $end = CarbonImmutable::parse($endString, $stationTz);
            $endTimestamp = $end->getTimestamp();

            $range = $start->format('Y-m-d_H-i-s') . '_to_' . $end->format('Y-m-d_H-i-s');

            $qb = $qb->andWhere('l.timestamp_start < :time_end')
                ->andWhere('(l.timestamp_end = 0 OR l.timestamp_end > :time_start)')
                ->setParameter('time_start', $startTimestamp)
                ->setParameter('time_end', $endTimestamp);
        }

        /** @var Locale $locale */
        $locale = $request->getAttribute(ServerRequest::ATTR_LOCALE);

        $mountNames = $mountRepo->getDisplayNames($station);
        $remoteNames = $remoteRepo->getDisplayNames($station);

        $listenersIterator = ReadOnlyBatchIteratorAggregate::fromQuery($qb->getQuery(), 250);

        /** @var Entity\Api\Listener[] $listeners */
        $listeners = [];
        $listenersByHash = [];

        $groupByUnique = ('false' !== ($params['unique'] ?? 'true'));

        foreach ($listenersIterator as $listener) {
            /** @var Entity\Listener $listener */
            $listenerStart = $listener->getTimestampStart();

            if ($isLive) {
                $listenerEnd = $now->getTimestamp();
            } else {
                if ($listenerStart < $startTimestamp) {
                    $listenerStart = $startTimestamp;
                }

                $listenerEnd = $listener->getTimestampEnd();
                if (0 === $listenerEnd || $listenerEnd > $endTimestamp) {
                    $listenerEnd = $endTimestamp;
                }
            }

            $hash = $listener->getListenerHash();
            if ($groupByUnique && isset($listenersByHash[$hash])) {
                $listenersByHash[$hash]['intervals'][] = [
                    'start' => $listenerStart,
                    'end' => $listenerEnd,
                ];
                continue;
            }

            $userAgent = $listener->getListenerUserAgent();
            $dd = $deviceDetector->parse($userAgent);

            if ($dd->isBot()) {
                $clientBot = (array)$dd->getBot();

                $clientBotName = $clientBot['name'] ?? 'Unknown Crawler';
                $clientBotType = $clientBot['category'] ?? 'Generic Crawler';
                $client = $clientBotName . ' (' . $clientBotType . ')';
            } else {
                $clientInfo = (array)$dd->getClient();
                $clientBrowser = $clientInfo['name'] ?? 'Unknown Browser';
                $clientVersion = $clientInfo['version'] ?? '0.00';

                $clientOsInfo = (array)$dd->getOs();
                $clientOs = $clientOsInfo['name'] ?? 'Unknown OS';

                $client = $clientBrowser . ' ' . $clientVersion . ', ' . $clientOs;
            }

            $api = new Entity\Api\Listener();
            $api->ip = $listener->getListenerIp();
            $api->user_agent = $userAgent;
            $api->client = $client;
            $api->is_mobile = $dd->isMobile();

            if ($listener->getMountId()) {
                $mountId = $listener->getMountId();

                $api->mount_is_local = true;
                $api->mount_name = $mountNames[$mountId];
            } elseif ($listener->getRemoteId()) {
                $remoteId = $listener->getRemoteId();

                $api->mount_is_local = false;
                $api->mount_name = $remoteNames[$remoteId];
            }

            $api->location = $geoLite->getLocationInfo($api->ip, $locale->getLocale());

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

                $startTime = $now->getTimestamp();
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
        $tempFile = tmpfile() ?: throw new \RuntimeException('Could not create temp file.');
        $csv = Writer::createFromStream($tempFile);

        $tz = $station->getTimezoneObject();

        $csv->insertOne(
            [
                'IP',
                'Start Time',
                'End Time',
                'Seconds Connected',
                'User Agent',
                'Client',
                'Is Mobile',
                'Mount Type',
                'Mount Name',
                'Location',
                'Country',
                'Region',
                'City',
            ]
        );

        foreach ($listeners as $listener) {
            $startTime = CarbonImmutable::createFromTimestamp($listener->connected_on, $tz);
            $endTime = CarbonImmutable::createFromTimestamp($listener->connected_until, $tz);

            $export_row = [
                $listener->ip,
                $startTime->toIso8601String(),
                $endTime->toIso8601String(),
                $listener->connected_time,
                $listener->user_agent,
                $listener->client,
                $listener->is_mobile ? 'True' : 'False',
            ];

            if ('' === $listener->mount_name) {
                $export_row[] = 'Unknown';
                $export_row[] = 'Unknown';
            } else {
                $export_row[] = ($listener->mount_is_local) ? 'Local' : 'Remote';
                $export_row[] = $listener->mount_name;
            }

            $location = $listener->location;
            if ('success' === $location['status']) {
                $export_row[] = $location['region'] . ', ' . $location['country'];
                $export_row[] = $location['country'];
                $export_row[] = $location['region'];
                $export_row[] = $location['city'];
            } else {
                $export_row[] = $location['message'] ?? 'N/A';
                $export_row[] = '';
                $export_row[] = '';
                $export_row[] = '';
            }

            $csv->insertOne($export_row);
        }

        $stream = new Stream($tempFile);

        return $response->renderStreamAsFile($stream, 'text/csv', $filename);
    }
}
