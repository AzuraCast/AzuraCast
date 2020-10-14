<?php

namespace App\Controller\Api\Stations;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\IpGeolocation;
use App\Utilities\Csv;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Mobile_Detect;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class ListenersController
{
    protected EntityManagerInterface $em;

    protected IpGeolocation $geoLite;

    public function __construct(EntityManagerInterface $em, IpGeolocation $geoLite)
    {
        $this->em = $em;
        $this->geoLite = $geoLite;
    }

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
     */
    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();
        $station_tz = $station->getTimezoneObject();

        $params = $request->getQueryParams();

        if (!empty($params['start'])) {
            $start = CarbonImmutable::parse($params['start'] . ' 00:00:00', $station_tz);
            $start_timestamp = $start->getTimestamp();

            $end = CarbonImmutable::parse(($params['end'] ?? $params['start']) . ' 23:59:59', $station_tz);
            $end_timestamp = $end->getTimestamp();

            $range = $start->format('Ymd') . '_to_' . $end->format('Ymd');

            $listeners_unsorted = $this->em->createQuery(/** @lang DQL */ 'SELECT
                l
                FROM App\Entity\Listener l
                WHERE l.station_id = :station_id
                AND l.timestamp_start < :time_end
                AND (l.timestamp_end = 0 OR l.timestamp_end > :time_start)')
                ->setParameter('station_id', $station->getId())
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

            $listeners_unsorted = $this->em->createQuery(/** @lang DQL */ 'SELECT
                l
                FROM App\Entity\Listener l
                WHERE l.station_id = :station_id
                AND l.timestamp_end = 0')
                ->setParameter('station_id', $station->getId())
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

        $detect = new Mobile_Detect();
        $locale = $request->getAttribute('locale');

        $format = $params['format'] ?? 'json';

        if ('csv' === $format) {
            $export_all = [
                [
                    'IP',
                    'Seconds Connected',
                    'User Agent',
                    'Is Mobile',
                    'Location',
                    'Country',
                    'Region',
                    'City',
                ],
            ];

            foreach ($listeners_raw as $listener) {
                $location = $this->geoLite->getLocationInfo($listener['listener_ip'], $locale);

                $export_row = [
                    (string)$listener['listener_ip'],
                    Entity\Listener::getListenerSeconds($listener['intervals']),
                    (string)$listener['listener_user_agent'],
                    $detect->isMobile($listener['listener_user_agent']) ? 'true' : 'false',
                ];

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

        $listeners = [];
        foreach ($listeners_raw as $listener) {
            $api = new Entity\Api\Listener();
            $api->ip = (string)$listener['listener_ip'];
            $api->user_agent = (string)$listener['listener_user_agent'];
            $api->is_mobile = $detect->isMobile($listener['listener_user_agent']);
            $api->connected_on = (int)$listener['timestamp_start'];
            $api->connected_time = Entity\Listener::getListenerSeconds($listener['intervals']);
            $api->location = $this->geoLite->getLocationInfo($listener['listener_ip'], $locale);

            $listeners[] = $api;
        }

        return $response->withJson($listeners);
    }
}
