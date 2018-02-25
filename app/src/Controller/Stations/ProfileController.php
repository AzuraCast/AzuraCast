<?php
namespace Controller\Stations;

use App\Cache;
use App\Flash;
use App\Mvc\View;
use AzuraCast\Radio\Backend\BackendAbstract;
use AzuraCast\Radio\Configuration;
use AzuraCast\Radio\Frontend\FrontendAbstract;
use Doctrine\ORM\EntityManager;
use Entity;
use App\Http\Request;
use App\Http\Response;

class ProfileController
{
    /** @var EntityManager */
    protected $em;

    /** @var Flash */
    protected $flash;

    /** @var Cache */
    protected $cache;

    /** @var Configuration */
    protected $configuration;

    /** @var Entity\Repository\StationRepository */
    protected $station_repo;

    /** @var array */
    protected $form_config;

    /**
     * ProfileController constructor.
     * @param EntityManager $em
     * @param Flash $flash
     * @param Cache $cache
     * @param Configuration $configuration
     * @param array $form_config
     */
    public function __construct(EntityManager $em, Flash $flash, Cache $cache, Configuration $configuration, array $form_config)
    {
        $this->em = $em;
        $this->flash = $flash;
        $this->cache = $cache;
        $this->configuration = $configuration;
        $this->form_config = $form_config;

        $this->station_repo = $em->getRepository(Entity\Station::class);
    }

    public function indexAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        /** @var FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        // Statistics about backend playback.
        $num_songs = $this->em->createQuery('SELECT COUNT(sm.id) FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sp.id IS NOT NULL AND sm.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        $num_playlists = $this->em->createQuery('SELECT COUNT(sp.id) FROM Entity\StationPlaylist sp WHERE sp.station_id = :station_id')
            ->setParameter('station_id', $station->getId())
            ->getSingleScalarResult();

        /** @var View $view */
        $view = $request->getAttribute('view');

        // Populate initial nowplaying data.
        $np = [
            'now_playing' => [
                'song' => [
                    'title' => _('Song Title'),
                    'artist' => _('Song Artist'),
                ],
                'is_request' => false,
                'elapsed' => 0,
                'duration' => 0,
            ],
            'listeners' => [
                'unique' => 0,
                'total' => 0,
            ],
            'playing_next' => [
                'song' => [
                    'title' => _('Song Title'),
                    'artist' => _('Song Artist'),
                ],
            ],
        ];

        $station_np = $station->getNowplaying();
        if ($station_np instanceof Entity\Api\NowPlaying) {
            $np['now_playing']['song']['title'] = $station_np->now_playing->song->title;
            $np['now_playing']['song']['artist'] = $station_np->now_playing->song->artist;
            $np['now_playing']['is_request'] = $station_np->now_playing->is_request;
            $np['now_playing']['elapsed'] = $station_np->now_playing->elapsed;
            $np['now_playing']['duration'] = $station_np->now_playing->duration;
            $np['listeners']['unique'] = $station_np->listeners->unique;
            $np['listeners']['total'] = $station_np->listeners->total;
            $np['playing_next']['song']['title'] = $station_np->playing_next->song->title;
            $np['playing_next']['song']['artist'] = $station_np->playing_next->song->artist;
        }

        return $view->renderToResponse($response, 'stations/profile/index', [
            'num_songs' => $num_songs,
            'num_playlists' => $num_playlists,
            'backend_type' => $station->getBackendType(),
            'backend_config' => (array)$station->getBackendConfig(),
            'backend_is_running' => $backend->isRunning(),
            'frontend_type' => $station->getFrontendType(),
            'frontend_config' => (array)$station->getFrontendConfig(),
            'frontend_is_running' => $frontend->isRunning(),
            'stream_urls' => $frontend->getStreamUrls(),
            'nowplaying' => $np,
        ]);
    }

    public function editAction(Request $request, Response $response, $station_id): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        $base_form = $this->form_config;
        unset($base_form['groups']['admin']);

        $form = new \AzuraForms\Form($base_form);

        $form->populate($this->station_repo->toArray($station));

        if (!empty($_POST) && $form->isValid($_POST)) {

            $data = $form->getValues();

            $old_frontend = $station->getFrontendType();
            $old_backend = $station->getBackendType();

            $this->station_repo->fromArray($station, $data);
            $this->em->persist($station);
            $this->em->flush();

            $frontend_changed = ($old_frontend !== $station->getFrontendType());
            $backend_changed = ($old_backend !== $station->getBackendType());
            $adapter_changed = $frontend_changed || $backend_changed;

            if ($frontend_changed) {
                $this->station_repo->resetMounts($station, $frontend);
            }

            $this->configuration->writeConfiguration($station, $adapter_changed);

            // Clear station cache.
            $this->cache->remove('stations');

            return $response->redirectToRoute('stations:profile:index', ['station' => $station_id]);
        }

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/profile/edit', [
            'form' => $form,
        ]);
    }
}
