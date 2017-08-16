<?php
namespace Controller\Stations;

use Entity\Station;

class ProfileController extends BaseController
{
    public function indexAction()
    {
        // Backend controller.
        $this->view->backend_type = $this->station->getBackendType();
        $this->view->backend_config = (array)$this->station->getBackendConfig();
        $this->view->backend_is_running = $this->backend->isRunning();

        // Frontend controller.
        $this->view->frontend_type = $this->station->getFrontendType();
        $this->view->frontend_config = $frontend_config = (array)$this->station->getFrontendConfig();
        $this->view->frontend_is_running = $this->frontend->isRunning();

        $this->view->stream_urls = $this->frontend->getStreamUrls();

        // Statistics about backend playback.
        $this->view->num_songs = $this->em->createQuery('SELECT COUNT(sm.id) FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sp.id IS NOT NULL AND sm.station_id = :station_id')
            ->setParameter('station_id', $this->station->getId())
            ->getSingleScalarResult();

        $this->view->num_playlists = $this->em->createQuery('SELECT COUNT(sp.id) FROM Entity\StationPlaylist sp WHERE sp.station_id = :station_id')
            ->setParameter('station_id', $this->station->getId())
            ->getSingleScalarResult();
    }

    public function editAction()
    {
        $this->acl->checkPermission('manage station profile', $this->station->getId());

        $base_form = $this->config->forms->station->toArray();
        unset($base_form['groups']['admin']);

        $form = new \App\Form($base_form);

        $form->setDefaults($this->station_repo->toArray($this->station));

        if (!empty($_POST) && $form->isValid($_POST)) {

            $data = $form->getValues();

            /*
            $files = $form->processFiles('stations');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            */

            $oldAdapter = $this->station->getFrontendType();

            $this->station_repo->fromArray($this->station, $data);
            $this->em->persist($this->station);
            $this->em->flush();

            if ($oldAdapter !== $this->station->getFrontendType()) {
                $this->station_repo->resetMounts($this->station, $this->di);
            }

            $this->station->writeConfiguration($this->di);

            // Clear station cache.
            $cache = $this->di->get('cache');
            $cache->remove('stations');

            return $this->redirectFromHere(['action' => 'index']);
        }

        $this->view->form = $form;
    }

    public function backendAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->getId());

        switch ($this->getParam('do', 'restart')) {
            case "skip":
                if (method_exists($this->backend, 'skip')) {
                    $this->backend->skip();
                }

                $this->alert('<b>' . _('Song skipped.') . '</b>', 'green');
                break;

            case "stop":
                $this->backend->stop();
                break;

            case "start":
                $this->backend->start();
                break;

            case "restart":
            default:
                $this->backend->stop();
                $this->backend->write();
                $this->backend->start();
                break;
        }

        return $this->redirectFromHere(['action' => 'index']);
    }

    public function frontendAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->getId());

        switch ($this->getParam('do', 'restart')) {
            case "stop":
                $this->frontend->stop();
                break;

            case "start":
                $this->frontend->start();
                break;

            case "restart":
            default:
                $this->frontend->stop();
                $this->frontend->write();
                $this->frontend->start();
                break;
        }

        return $this->redirectFromHere(['action' => 'index']);
    }
}
