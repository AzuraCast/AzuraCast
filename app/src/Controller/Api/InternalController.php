<?php
namespace Controller\Api;

use AzuraCast\Acl\StationAcl;
use AzuraCast\Radio\Adapters;
use AzuraCast\Radio\Backend\Liquidsoap;
use AzuraCast\Sync\Task\NowPlaying;
use Entity;
use App\Http\Request;
use App\Http\Response;

class InternalController
{
    /** @var StationAcl */
    protected $acl;

    /** @var Adapters */
    protected $adapters;

    /** @var NowPlaying */
    protected $sync_nowplaying;

    /**
     * InternalController constructor.
     * @param StationAcl $acl
     * @param Adapters $adapters
     * @param NowPlaying $sync_nowplaying
     */
    public function __construct(StationAcl $acl, Adapters $adapters, NowPlaying $sync_nowplaying)
    {
        $this->acl = $acl;
        $this->adapters = $adapters;
        $this->sync_nowplaying = $sync_nowplaying;
    }

    public function authAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        if (!$station->getEnableStreamers()) {
            return $response->write('false');
        }

        $user = $request->getParam('dj_user');
        $pass = $request->getParam('dj_password');

        $adapter = $this->adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            return $response->write($adapter->authenticateStreamer($user, $pass));
        }
        return $response->write('false');
    }

    public function nextsongAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $as_autodj = $request->hasParam('api_auth');

        $adapter = $this->adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            return $response->write($adapter->getNextSong($as_autodj));
        }

        return $response->write('');
    }

    public function djonAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $adapter = $this->adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $adapter->toggleLiveStatus(true);
        }

        return $response->write('received');
    }

    public function djoffAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $adapter = $this->adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $adapter->toggleLiveStatus(true);
        }

        return $response->write('received');
    }

    public function notifyAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        $payload = $request->getBody()->getContents();

        if (!APP_IN_PRODUCTION) {
            $log = date('Y-m-d g:i:s')."\n".$station->getName()."\n".$payload."\n\n";
            file_put_contents(APP_INCLUDE_TEMP.'/notify.log', $log, \FILE_APPEND);
        }

        $this->sync_nowplaying->processStation($station, $payload);

        return $response->write('received');
    }

    /**
     * @param Request $request
     * @return bool
     * @throws \App\Exception\PermissionDenied
     */
    protected function _checkStationAuth(Request $request)
    {
        /** @var Entity\Station $station */
        $station = $request->getAttribute('station');

        /** @var Entity\User $user */
        $user = $request->getAttribute('user');

        if ($this->acl->userAllowed($user, 'view administration', $station->getId())) {
            return true;
        }

        $auth_key = $request->getParam('api_auth');
        if (!$station->validateAdapterApiKey($auth_key)) {
            throw new \App\Exception\PermissionDenied();
        }
    }
}