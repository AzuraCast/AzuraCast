<?php
namespace Controller\Api;

use Entity;

class InternalController extends BaseController
{
    /** @var Entity\Station $station */
    protected $station;

    protected function preDispatch()
    {
        parent::preDispatch();

        $station_id = (int)$this->getParam('station');
        $station = $this->em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station)) {
            throw new \App\Exception('Station not found.');
        }

        try {
            $this->checkStationPermission($station, 'view administration');
        } catch (\App\Exception\PermissionDenied $e) {
            $auth_key = $this->getParam('api_auth', '');
            if (!$station->validateAdapterApiKey($auth_key)) {
                throw new \App\Exception\PermissionDenied();
            }
        }

        $this->station = $station;
    }

    public function authAction()
    {
        $user = $this->getParam('dj_user');
        $pass = $this->getParam('dj_password');

        if ($this->getParam('dj_user') == 'shoutcast') {
            list($user, $pass) = explode(':', $pass);
        }

        if (!$this->station->getEnableStreamers()) {
            return $this->_return('false');
        }

        $fe_config = (array)$this->station->getFrontendConfig();
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
        $backend_adapter = $this->station->getBackendAdapter($this->di);

        if (!($backend_adapter instanceof \AzuraCast\Radio\Backend\LiquidSoap)) {
            throw new \App\Exception('Not a LiquidSoap station.');
        }

        /** @var Entity\Repository\SongHistoryRepository $history_repo */
        $history_repo = $this->em->getRepository(Entity\SongHistory::class);

        /** @var Entity\SongHistory|null $sh */
        $sh = $history_repo->getNextSongForStation($this->station, $this->hasParam('api_auth'));

        if ($sh instanceof Entity\SongHistory) {
            // 'annotate:type=\"song\",album=\"$ALBUM\",display_desc=\"$FULLSHOWNAME\",liq_start_next=\"2.5\",liq_fade_in=\"3.5\",liq_fade_out=\"3.5\":$SONGPATH'
            $song_path = $sh->getMedia()->getFullPath();

            return $this->_return('annotate:' . implode(',', $sh->getMedia()->getAnnotations()) . ':' . $song_path);
        } else {
            $error_mp3_path = (APP_INSIDE_DOCKER) ? '/usr/local/share/icecast/web/error.mp3' : APP_INCLUDE_ROOT . '/resources/error.mp3';
            return $this->_return($error_mp3_path);
        }
    }

    public function notifyAction()
    {
        $payload = $this->request->getBody()->getContents();

        if (!APP_IN_PRODUCTION) {
            $log = date('Y-m-d g:i:s')."\n".$this->station->getName()."\n".$payload."\n\n";
            file_put_contents(APP_INCLUDE_TEMP.'/notify.log', $log, \FILE_APPEND);
        }

        $np_sync = new \AzuraCast\Sync\NowPlaying($this->di);
        $np_sync->processStation($this->station, $payload);

        return $this->_return('received');
    }

    protected function _return($output)
    {
        $this->response->getBody()->write($output);
        return $this->response;
    }
}