<?php
namespace App\Controller\Api;

use App\Acl;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task\NowPlaying;
use App\Entity;
use App\Http\Request;
use App\Http\Response;

class InternalController
{
    /** @var Acl */
    protected $acl;

    /** @var NowPlaying */
    protected $sync_nowplaying;

    /**
     * @param Acl $acl
     * @param NowPlaying $sync_nowplaying
     * @see \App\Provider\ApiProvider
     */
    public function __construct(Acl $acl, NowPlaying $sync_nowplaying)
    {
        $this->acl = $acl;
        $this->sync_nowplaying = $sync_nowplaying;
    }

    public function authAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        $station = $request->getStation();
        if (!$station->getEnableStreamers()) {
            return $response->write('false');
        }

        $user = $request->getParam('dj_user');
        $pass = $request->getParam('dj_password');

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            return $response->write($adapter->authenticateStreamer($user, $pass));
        }

        return $response->write('false');
    }

    public function nextsongAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        $as_autodj = $request->hasParam('api_auth');

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            return $response->write($adapter->getNextSong($as_autodj));
        }

        return $response->write('');
    }

    public function djonAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $adapter->toggleLiveStatus(true);
        }

        return $response->write('received');
    }

    public function djoffAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $adapter->toggleLiveStatus(false);
        }

        return $response->write('received');
    }

    public function notifyAction(Request $request, Response $response): Response
    {
        $this->_checkStationAuth($request);

        $station = $request->getStation();

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
     * @throws \App\Exception
     * @throws \App\Exception\PermissionDenied
     */
    protected function _checkStationAuth(Request $request): void
    {
        $station = $request->getStation();

        /** @var Entity\User $user */
        $user = $request->getAttribute(Request::ATTRIBUTE_USER);

        if ($this->acl->userAllowed($user, 'view administration', $station->getId())) {
            return;
        }

        $auth_key = $request->getParam('api_auth');
        if (!$station->validateAdapterApiKey($auth_key)) {
            throw new \App\Exception\PermissionDenied();
        }

        return;
    }
}
