<?php
namespace App\Controller\Api;

use App\Cache;
use Doctrine\ORM\EntityManager;
use App\Entity;
use App\Http\Request;
use App\Http\Response;
use MaxMind\Db\Reader;

class ListenersController
{
    /** @var EntityManager */
    protected $em;

    /** @var Cache */
    protected $cache;

    /** @var Reader */
    protected $geoip;

    /**
     * @param EntityManager $em
     * @param Cache $cache
     * @param Reader $geoip
     * @see \App\Provider\ApiProvider
     */
    public function __construct(EntityManager $em, Cache $cache, Reader $geoip)
    {
        $this->em = $em;
        $this->cache = $cache;
        $this->geoip = $geoip;
    }

    /**
     * @OA\Get(path="/station/{station_id}/listeners",
     *   tags={"Stations: Listeners"},
     *   description="Return detailed information about current listeners.",
     *   @OA\Parameter(ref="#/components/parameters/station_id_required"),
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *     @OA\Schema(
     *       type="array",
     *       @OA\Items(ref="#/components/schemas/Listener")
     *     )
     *   ),
     *   @OA\Response(response=404, description="Station not found"),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={
     *     {"api_key": {"view station reports"}}
     *   },
     * )
     */
    public function indexAction(Request $request, Response $response): Response
    {
        $station = $request->getStation();

        if ($request->getParam('start') !== null) {

            $start = strtotime($request->getParam('start').' 00:00:00');
            $end = strtotime($request->getParam('end', $request->getParam('start')).' 23:59:59');

            $listeners_unsorted = $this->em->createQuery('SELECT l FROM '.Entity\Listener::class.' l
                WHERE l.station_id = :station_id
                AND l.timestamp_start < :end
                AND l.timestamp_end > :start')
                ->setParameter('station_id', $station->getId())
                ->setParameter('start', $start)
                ->setParameter('end', $end)
                ->getArrayResult();

            $listeners_raw = [];
            foreach($listeners_unsorted as $listener) {

                $hash = $listener['listener_hash'];
                if (!isset($listeners_raw[$hash])) {
                    $listener['connected_time'] = 0;
                    $listeners_raw[$hash] = $listener;
                }

                $listeners_raw[$hash]['connected_time'] += ($listener['timestamp_end'] - $listener['timestamp_start']);
            }
        } else {
            $listeners_raw = $this->em->createQuery('SELECT l FROM '.Entity\Listener::class.' l
                WHERE l.station_id = :station_id
                AND l.timestamp_end = 0')
                ->setParameter('station_id', $station->getId())
                ->getArrayResult();
        }

        $detect = new \Mobile_Detect;
        $locale = $request->getAttribute('locale');

        $listeners = [];
        foreach($listeners_raw as $listener) {
            $detect->setUserAgent($listener['listener_user_agent']);

            $api = new Entity\Api\Listener;
            $api->ip = (string)$listener['listener_ip'];
            $api->user_agent = (string)$listener['listener_user_agent'];
            $api->is_mobile = $detect->isMobile();
            $api->connected_on = (int)$listener['timestamp_start'];
            $api->connected_time = $listener['connected_time'] ?? (time() - $listener['timestamp_start']);
            $api->location = $this->_getLocationInfo($listener['listener_ip'], $locale);

            $listeners[] = $api;
        }

        return $response->withJson($listeners);
    }

    protected function _getLocationInfo($ip, $locale): array
    {
        $ip_info = $this->geoip->get($ip);

        if (empty($ip_info)) {
            return [
                'status' => 'error',
                'message' => 'Internal/Reserved IP',
            ];
        }

        return [
            'status' => 'success',
            'lat' => $ip_info['location']['latitude'] ?? 0.0,
            'lon' => $ip_info['location']['longitude'] ?? 0.0,
            'timezone' => $ip_info['location']['time_zone'] ?? '',
            'region' => $this->_getLocalizedString($ip_info['subdivisions'][0]['names'] ?? null, $locale),
            'country' => $this->_getLocalizedString($ip_info['country']['names'] ?? null, $locale),
            'city' => $this->_getLocalizedString($ip_info['city']['names'] ?? null, $locale),
            'message' => 'This product includes GeoLite2 data created by MaxMind, available from <a href="http://www.maxmind.com">http://www.maxmind.com</a>.',
        ];
    }

    protected function _getLocalizedString($names, $locale): string
    {
        if (empty($names)) {
            return '';
        }

        // Convert "en_US" to "en-US", the format MaxMind uses.
        $locale = str_replace('_', '-', $locale);

        // Check for an exact match.
        if (isset($names[$locale])) {
            return $names[$locale];
        }

        // Check for a match of the first portion, i.e. "en"
        $locale = strtolower(substr($locale, 0, 2));
        if (isset($names[$locale])) {
            return $names[$locale];
        }

        return $names['en'];
    }
}
