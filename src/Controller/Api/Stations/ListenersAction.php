<?php

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\DeviceDetector;
use App\Service\IpGeolocation;
use App\Utilities\Csv;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mobile_Detect;
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
     *
     * @param ServerRequest $request
     * @param Response $response
     * @param EntityManagerInterface $em
     * @param Entity\Repository\StationMountRepository $mountRepo
     * @param Entity\Repository\StationRemoteRepository $remoteRepo
     * @param IpGeolocation $geoLite
     * @param DeviceDetector $deviceDetector
     *
     */
    public function __invoke(
        ServerRequest $request,
        Response $response,
        EntityManagerInterface $em,
        Entity\Repository\StationMountRepository $mountRepo,
        Entity\Repository\StationRemoteRepository $remoteRepo,
        IpGeolocation $geoLite,
        DeviceDetector $deviceDetector
    ): ResponseInterface {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        $params = $request->getQueryParams();

        $mountNames = $mountRepo->getDisplayNames($station);
        $remoteNames = $remoteRepo->getDisplayNames($station);

        if (!empty($params['start'])) {
            $start = CarbonImmutable::parse($params['start'] . ' 00:00:00', $station_tz);
            $start_timestamp = $start->getTimestamp();

            $end = CarbonImmutable::parse(($params['end'] ?? $params['start']) . ' 23:59:59', $station_tz);
            $end_timestamp = $end->getTimestamp();

            $range = $start->format('Ymd') . '_to_' . $end->format('Ymd');

            $listeners_unsorted = $em->createQuery(
                <<<'DQL'
                    SELECT l
                    FROM App\Entity\Listener l
                    WHERE l.station_id = :station_id
                    AND l.timestamp_start < :time_end
                    AND (l.timestamp_end = 0 OR l.timestamp_end > :time_start)
                DQL
            )->setParameter('station_id', $station->getId())
                ->setParameter('time_start', $start_timestamp)
                ->setParameter('time_end', $end_timestamp)
                ->getArrayResult();

            $listeners_raw = [];
            foreach ($listeners_unsorted as $listener) {
                $hash = $listener['listener_hash'];
                if (!isset($listeners_raw[$hash])) {
                    $listener['intervals'] = [];
                    $listeners_raw[$hash] = $listener;
                }

                $listener_start = (int)$listener['timestamp_start'];
                if ($listener_start < $start_timestamp) {
                    $listener_start = $start_timestamp;
                }

                $listener_end = (int)$listener['timestamp_end'];
                if (0 === $listener_end || $listener_end > $end_timestamp) {
                    $listener_end = $end_timestamp;
                }

                $listeners_raw[$hash]['intervals'][] = [
                    'start' => $listener_start,
                    'end' => $listener_end,
                ];
            }
        } else {
            $range = 'live';

            $listeners_unsorted = $em->createQuery(
                <<<'DQL'
                    SELECT l
                    FROM App\Entity\Listener l
                    WHERE l.station_id = :station_id
                    AND l.timestamp_end = 0
                DQL
            )->setParameter('station_id', $station->getId())
                ->getArrayResult();

            $listeners_raw = [];

            foreach ($listeners_unsorted as $listener) {
                $hash = $listener['listener_hash'];
                if (!isset($listeners_raw[$hash])) {
                    $listener['intervals'] = [];
                    $listeners_raw[$hash] = $listener;
                }

                $listener_start = (int)$listener['timestamp_start'];
                $listener_end = time();

                $listeners_raw[$hash]['intervals'][] = [
                    'start' => $listener_start,
                    'end' => $listener_end,
                ];
            }
        }

        $locale = $request->getAttribute('locale');

        /** @var Entity\Api\Listener[] $listeners */
        $listeners = [];
        foreach ($listeners_raw as $listener) {
            $userAgent = (string)$listener['listener_user_agent'];
            $dd = $deviceDetector->parse($userAgent);

            if ($dd->isBot()) {
                $clientBot = $dd->getBot();

                $clientBotName = $clientBot['name'] ?? 'Unknown Crawler';
                $clientBotType = $clientBot['category'] ?? 'Generic Crawler';
                $client = $clientBotName . ' (' . $clientBotType . ')';
            } else {
                $clientInfo = $dd->getClient();
                $clientBrowser = $clientInfo['name'] ?? 'Unknown Browser';
                $clientVersion = $clientInfo['version'] ?? '0.00';

                $clientOsInfo = $dd->getOs();
                $clientOs = $clientOsInfo['name'] ?? 'Unknown OS';

                $client = $clientBrowser . ' ' . $clientVersion . ', ' . $clientOs;
            }

            $api = new Entity\Api\Listener();
            $api->ip = (string)$listener['listener_ip'];
            $api->user_agent = $userAgent;
            $api->client = $client;
            $api->is_mobile = $dd->isMobile();

            if ($listener['mount_id']) {
                $mountId = (int)$listener['mount_id'];

                $api->mount_is_local = true;
                $api->mount_name = $mountNames[$mountId];
            } elseif ($listener['remote_id']) {
                $remoteId = (int)$listener['remote_id'];

                $api->mount_is_local = false;
                $api->mount_name = $remoteNames[$remoteId];
            }

            $api->connected_on = (int)$listener['timestamp_start'];
            $api->connected_time = Entity\Listener::getListenerSeconds($listener['intervals']);
            $api->location = $geoLite->getLocationInfo($listener['listener_ip'], $locale);

            $listeners[] = $api;
        }

        $format = $params['format'] ?? 'json';

        if ('csv' === $format) {
            $export_all = [
                [
                    'IP',
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
                ],
            ];

            foreach ($listeners as $listener) {
                $export_row = [
                    $listener->ip,
                    $listener->connected_time,
                    $listener->user_agent,
                    $listener->client,
                    $listener->is_mobile,
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

                $export_all[] = $export_row;
            }

            $csv_file = Csv::arrayToCsv($export_all);
            $csv_filename = $station->getShortName() . '_listeners_' . $range . '.csv';

            return $response->renderStringAsFile($csv_file, 'text/csv', $csv_filename);
        }

        return $response->withJson($listeners);
    }
}
