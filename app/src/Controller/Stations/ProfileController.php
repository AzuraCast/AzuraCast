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
        ]);
    }

    public function editAction(Request $request, Response $response): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        $base_form = $this->form_config;
        unset($base_form['groups']['admin']);

        $form = new \App\Form($base_form);

        $form->setDefaults($this->station_repo->toArray($station));

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

            return $response->redirectToRoute('stations:profile:index', ['station' => $station->getId()]);
        }

        /** @var View $view */
        $view = $request->getAttribute('view');

        return $view->renderToResponse($response, 'stations/profile/edit', [
            'form' => $form,
        ]);
    }

    public function backendAction(Request $request, Response $response, $station_id, $do = 'restart'): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var BackendAbstract $backend */
        $backend = $request->getAttribute('station_backend');

        switch ($do) {
            case "skip":
                if (method_exists($backend, 'skip')) {
                    $backend->skip();
                }

                if ($request->isXhr()) {
                    return $response->withJson([
                        'message' => _('Song skipped.'),
                        'type' => 'success',
                    ]);
                } else {
                    $this->flash->alert('<b>' . _('Song skipped.') . '</b>', 'success');
                }
                break;

            case "stop":
                $backend->stop();
                break;

            case "start":
                $backend->start();
                break;

            case "restart":
            default:
                $backend->stop();
                $backend->write();
                $backend->start();
                break;
        }

        return $response->redirectToRoute('stations:profile:index', ['station' => $station->getId()]);
    }

    public function frontendAction(Request $request, Response $response, $station_id, $do = 'restart'): Response
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var FrontendAbstract $frontend */
        $frontend = $request->getAttribute('station_frontend');

        switch ($do) {
            case "stop":
                $frontend->stop();
                break;

            case "start":
                $frontend->start();
                break;

            case "restart":
            default:
                $frontend->stop();
                $frontend->write();
                $frontend->start();
                break;
        }

        return $response->redirectToRoute('stations:profile:index', ['station' => $station->getId()]);
    }
}
