<?php
namespace Modules\Stations\Controllers;

use \Entity\Station;
use \Entity\Settings;

class ProfileController extends BaseController
{
    public function indexAction()
    {
        // Backend controller.
        $ba = $this->station->getBackendAdapter($this->di);

        $this->view->backend_adapter = $ba;
        $this->view->backend_type = $this->station->backend_type;
        $this->view->backend_config = (array)$this->station->backend_config;
        $this->view->backend_is_running = $ba->isRunning();

        // Frontend controller.
        $fa = $this->station->getFrontendAdapter($this->di);

        $this->view->frontend_adapter = $fa;
        $this->view->frontend_type = $this->station->frontend_type;
        $this->view->frontend_config = $frontend_config = (array)$this->station->frontend_config;
        $this->view->frontend_is_running = $fa->isRunning();

        $this->view->stream_urls = $fa->getStreamUrls();

        // Statistics about backend playback.
        $this->view->num_songs = $this->em->createQuery('SELECT COUNT(sm.id) FROM Entity\StationMedia sm LEFT JOIN sm.playlists sp WHERE sp.id IS NOT NULL AND sm.station_id = :station_id')
            ->setParameter('station_id', $this->station->id)
            ->getSingleScalarResult();

        $this->view->num_playlists = $this->em->createQuery('SELECT COUNT(sp.id) FROM Entity\StationPlaylist sp WHERE sp.station_id = :station_id')
            ->setParameter('station_id', $this->station->id)
            ->getSingleScalarResult();
    }

    public function editAction()
    {
        $this->acl->checkPermission('manage station profile', $this->station->id);

        $base_form = $this->module_config['admin']->forms->station->toArray();
        unset($base_form['groups']['admin']);

        $form = new \App\Form($base_form);

        $form->setDefaults($this->station->toArray($this->em));

        if(!empty($_POST) && $form->isValid($_POST))
        {
            $data = $form->getValues();

            /*
            $files = $form->processFiles('stations');
            foreach($files as $file_field => $file_paths)
                $data[$file_field] = $file_paths[1];
            */

            $this->station->fromArray($this->em, $data);
            $this->em->persist($this->station);
            $this->em->flush();

            // Clear station cache.
            $cache = $this->di->get('cache');
            $cache->remove('stations');

            return $this->redirectFromHere(array('action' => 'index'));
        }

        $this->view->form = $form;
    }

    public function backendAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->id);

        $adapter = $this->station->getBackendAdapter($this->di);

        switch($this->getParam('do', 'restart'))
        {
            case "skip":
                if (method_exists($adapter, 'skip'))
                    $adapter->skip();

                $this->alert('<b>'._('Song skipped.').'</b>', 'green');
            break;

            case "stop":
                $adapter->stop();

                $this->alert('<b>'._('Adapter stopped.').'</b>', 'green');
            break;

            case "start":
                $adapter->start();

                $this->alert('<b>'._('Adapter started.').'</b>', 'green');
            break;

            case "restart":
            default:
                $adapter->stop();
                $adapter->write();
                $adapter->start();

                $this->alert('<b>'._('Adapter rebooted.').'</b>', 'green');
            break;
        }

        return $this->redirectFromHere(['action' => 'index']);
    }

    public function frontendAction()
    {
        $this->acl->checkPermission('manage station broadcasting', $this->station->id);

        $adapter = $this->station->getFrontendAdapter($this->di);

        switch($this->getParam('do', 'restart'))
        {
            case "stop":
                $adapter->stop();

                $this->alert('<b>'._('Adapter stopped.').'</b>', 'green');
            break;

            case "start":
                $adapter->start();

                $this->alert('<b>'._('Adapter started.').'</b>', 'green');
            break;

            case "restart":
            default:
                $adapter->stop();
                $adapter->write();
                $adapter->start();

                $this->alert('<b>'._('Adapter rebooted.').'</b>', 'green');
            break;
        }

        return $this->redirectFromHere(['action' => 'index']);
    }
}
