<?php
namespace App\Controller\Frontend;

use App\Acl;
use App\Entity;
use App\Event;
use App\EventDispatcher;
use App\Http\Response;
use App\Http\Router;
use App\Http\ServerRequest;
use App\Radio\Adapters;
use Carbon\CarbonImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\SimpleCache\CacheInterface;
use stdClass;

class DashboardController
{
    protected EntityManagerInterface $em;

    protected Entity\Repository\SettingsRepository $settingsRepo;

    protected Acl $acl;

    protected CacheInterface $cache;

    protected Router $router;

    protected Adapters $adapter_manager;

    protected EventDispatcher $dispatcher;

    public function __construct(
        EntityManagerInterface $em,
        Entity\Repository\SettingsRepository $settingsRepo,
        Acl $acl,
        CacheInterface $cache,
        Adapters $adapter_manager,
        EventDispatcher $dispatcher
    ) {
        $this->em = $em;
        $this->settingsRepo = $settingsRepo;
        $this->acl = $acl;
        $this->cache = $cache;
        $this->adapter_manager = $adapter_manager;
        $this->dispatcher = $dispatcher;
    }

    public function indexAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $view = $request->getView();
        $user = $request->getUser();
        $router = $request->getRouter();

        $show_admin = $this->acl->userAllowed($user, Acl::GLOBAL_VIEW);

        /** @var Entity\Station[] $stations */
        $stations = $this->em->getRepository(Entity\Station::class)->findAll();

        // Don't show stations the user can't manage.
        $stations = array_filter($stations, function ($station) use ($user) {
            /** @var Entity\Station $station */
            return $station->isEnabled() &&
                $this->acl->userAllowed($user, Acl::STATION_VIEW, $station->getId());
        });

        if (empty($stations) && !$show_admin) {
            return $view->renderToResponse($response, 'frontend/index/noaccess');
        }

        // Get administrator notifications.
        $notification_event = new Event\GetNotifications($user, $request);
        $this->dispatcher->dispatch($notification_event);

        $notifications = $notification_event->getNotifications();

        $view_stations = [];
        $station_ids = [];

        // Generate initial data for station dashboard view.
        foreach ($stations as $row) {
            $frontend_adapter = $this->adapter_manager->getFrontendAdapter($row);

            $np = [
                'now_playing' => [
                    'song' => [
                        'title' => '',
                        'artist' => '',
                    ],
                ],
                'listeners' => [
                    'current' => 0,
                ],
            ];

            $station_np = $row->getNowplaying();
            if ($station_np instanceof Entity\Api\NowPlaying) {
                $np['now_playing']['song']['title'] = $station_np->now_playing->song->title;
                $np['now_playing']['song']['artist'] = $station_np->now_playing->song->artist;
                $np['listeners']['current'] = $station_np->listeners->current;
            }

            $view_stations[$row->getId()] = [
                'station' => [
                    'id' => $row->getId(),
                    'name' => $row->getName(),
                    'short_name' => $row->getShortName(),
                ],
                'public_url' => (string)$router->named('public:index', ['station_id' => $row->getShortName()]),
                'manage_url' => (string)$router->named('stations:index:index', ['station_id' => $row->getId()]),
                'stream_url' => (string)$frontend_adapter->getStreamUrl($row),
                'np' => $np,
            ];
            $station_ids[] = $row->getId();
        }

        // Detect current analytics level.
        $analytics_level = $this->settingsRepo->getSetting(Entity\Settings::LISTENER_ANALYTICS,
            Entity\Analytics::LEVEL_ALL);

        if ($analytics_level === Entity\Analytics::LEVEL_NONE) {
            $metrics = null;
        } else {
            // Generate unique cache ID for stations.
            $stats_cache_stations = [];
            foreach ($stations as $station) {
                $stats_cache_stations[$station->getId()] = $station->getId();
            }

            $cache_name = 'homepage_metrics_' . implode(',', $stats_cache_stations);

            $metrics = $this->cache->get($cache_name);
            if (empty($metrics)) {
                $metrics = $this->_getMetrics($view_stations, $show_admin);
                $this->cache->set($cache_name, $metrics, 600);
            }
        }

        return $view->renderToResponse($response, 'frontend/index/index', [
            'stations' => ['stations' => $view_stations],
            'station_ids' => $station_ids,
            'show_admin' => $show_admin,
            'metrics' => $metrics,
            'notifications' => $notifications,
        ]);
    }

    protected function _getMetrics(array $view_stations, bool $show_admin = false): array
    {
        // Statistics by day.
        $station_averages = [];

        $threshold = CarbonImmutable::parse('-180 days');

        $stats = $this->em->createQuery(/** @lang DQL */ 'SELECT a.station_id, a.moment, a.number_avg, a.number_unique 
            FROM App\Entity\Analytics a
            WHERE a.station_id IS NULL OR a.station_id IN (:stations)
            AND a.type = :type
            AND a.moment >= :threshold')
            ->setParameter('stations', $view_stations)
            ->setParameter('type', Entity\Analytics::INTERVAL_DAILY)
            ->setParameter('threshold', $threshold)
            ->getArrayResult();

        foreach ($stats as $row) {
            $station_id = $row['station_id'] ?? 'all';

            /** @var CarbonImmutable $moment */
            $moment = $row['moment'];

            $moment = $moment->getTimestamp() * 1000;

            $station_averages[$station_id][$moment] = [
                $moment,
                round($row['number_avg'], 2),
            ];
        }

        $metric_stations = [];
        if ($show_admin && count($view_stations) > 1) {
            $metric_stations['all'] = __('All Stations');
        }
        foreach ($view_stations as $station_id => $station_info) {
            $metric_stations[$station_id] = $station_info['station']['name'];
        }

        $station_metrics = [];
        $station_metrics_alt = [];

        foreach ($metric_stations as $station_id => $station_name) {
            if (isset($station_averages[$station_id])) {
                $series_obj = new stdClass;
                $series_obj->label = $station_name;
                $series_obj->type = 'line';
                $series_obj->fill = false;

                $station_metrics_alt[] = '<p>' . $series_obj->label . '</p>';
                $station_metrics_alt[] = '<dl>';

                ksort($station_averages[$station_id]);

                $series_data = [];
                foreach ($station_averages[$station_id] as $serie) {
                    $series_row = new stdClass;
                    $series_row->t = $serie[0];
                    $series_row->y = $serie[1];
                    $series_data[] = $series_row;

                    $serie_date = gmdate('Y-m-d', $serie[0] / 1000);
                    $station_metrics_alt[] = '<dt><time data-original="' . $serie[0] . '">' . $serie_date . '</time></dt>';
                    $station_metrics_alt[] = '<dd>' . $serie[1] . ' ' . __('Listeners') . '</dd>';
                }

                $station_metrics_alt[] = '</dl>';

                $series_obj->data = $series_data;

                $station_metrics[] = $series_obj;
            }
        }

        return [
            'station' => json_encode($station_metrics, JSON_THROW_ON_ERROR),
            'station_alt' => implode('', $station_metrics_alt),
        ];
    }
}
