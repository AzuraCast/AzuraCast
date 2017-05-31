<?php
namespace Controller\Api;

use Entity;

class InternalController extends BaseController
{
    /** @var Entity\Station $station */
    protected $station;

    public function preDispatch()
    {
        $station_id = (int)$this->getParam('station');
        $station = $this->em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station)) {
            throw new \App\Exception('Station not found.');
        }

        $backend_adapter = $station->getBackendAdapter($this->di);

        if (!($backend_adapter instanceof \AzuraCast\Radio\Backend\LiquidSoap)) {
            throw new \App\Exception('Not a LiquidSoap station.');
        }

        $auth_key = $this->getParam('api_auth', '');
        if (!$backend_adapter->validateApiPassword($auth_key)) {
            throw new \App\Exception\PermissionDenied();
        }

        $this->station = $station;
    }

    public function authAction()
    {
        $user = $this->getParam('dj_user');
        $pass = $this->getParam('dj_pass');

        if ($this->getParam('dj_user') == 'shoutcast') {
            list($user, $pass) = explode(':', $pass);
        }

        if (!$this->station->enable_streamers) {
            return $this->_return('false');
        }

        $fe_config = (array)$this->station->frontend_config;
        if (!empty($fe_config['source_pw']) && strcmp($fe_config['source_pw'], $pass) === 0) {
            return $this->_return('true');
        }

        if ($this->di['em']->getRepository(Entity\StationStreamer::class)->authenticate($this->station, $user, $pass)) {
            return $this->_return('true');
        } else {
            return $this->_return('false');
        }
    }

    public function nextsongAction()
    {
        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $this->em->getRepository(Entity\StationMedia::class);

        return $this->_return($media_repo->getNextSong($this->station));
    }

    protected function _return($output)
    {
        $this->response->getBody()->write($output);
        return $this->response;
    }
}