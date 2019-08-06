<?php
namespace App\Controller\Api;

use App\Acl;
use App\Entity;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task\NowPlaying;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class InternalController
{
    /** @var Acl */
    protected $acl;

    /** @var NowPlaying */
    protected $sync_nowplaying;

    /** @var AutoDJ */
    protected $autodj;

    /** @var Logger */
    protected $logger;

    /**
     * @param Acl $acl
     * @param NowPlaying $sync_nowplaying
     * @param AutoDJ $autodj
     * @param Logger $logger
     *
     * @see \App\Provider\ApiProvider
     */
    public function __construct(
        Acl $acl,
        NowPlaying $sync_nowplaying,
        AutoDJ $autodj,
        Logger $logger
    ) {
        $this->acl = $acl;
        $this->sync_nowplaying = $sync_nowplaying;
        $this->autodj = $autodj;
        $this->logger = $logger;
    }

    public function authAction(Request $request, Response $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $station = \App\Http\RequestHelper::getStation($request);
        if (!$station->getEnableStreamers()) {
            $this->logger->error('Attempted DJ authentication when streamers are disabled on this station.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            return $response->write('false');
        }

        $user = $request->getParam('dj_user');
        $pass = $request->getParam('dj_password');

        $adapter = \App\Http\RequestHelper::getStationBackend($request);
        if ($adapter instanceof Liquidsoap) {
            return $response->write($adapter->authenticateStreamer($station, $user, $pass));
        }

        return $response->write('false');
    }

    public function nextsongAction(Request $request, Response $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $as_autodj = $request->hasParam('api_auth');

        return $response->write($this->autodj->annotateNextSong(\App\Http\RequestHelper::getStation($request), $as_autodj));
    }

    public function djonAction(Request $request, Response $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $adapter = \App\Http\RequestHelper::getStationBackend($request);
        if ($adapter instanceof Liquidsoap) {
            $station = \App\Http\RequestHelper::getStation($request);

            $this->logger->info('Received "DJ connected" ping from Liquidsoap.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            $adapter->toggleLiveStatus($station, true);
        }

        return $response->write('received');
    }

    public function djoffAction(Request $request, Response $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $adapter = \App\Http\RequestHelper::getStationBackend($request);
        if ($adapter instanceof Liquidsoap) {
            $station = \App\Http\RequestHelper::getStation($request);

            $this->logger->info('Received "DJ disconnected" ping from Liquidsoap.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            $adapter->toggleLiveStatus($station, false);
        }

        return $response->write('received');
    }

    public function feedbackAction(Request $request, Response $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $station = \App\Http\RequestHelper::getStation($request);

        $body = $request->getParsedBody();

        $this->sync_nowplaying->queueStation($station, [
            'song_id'   => $body['song'] ?? null,
            'media_id'  => $body['media'] ?? null,
            'playlist_id'  => $body['playlist'] ?? null,
        ]);

        return $response->write('OK');
    }

    /**
     * @param Request $request
     * @throws \Azura\Exception
     * @throws \App\Exception\PermissionDenied
     */
    protected function _checkStationAuth(Request $request): void
    {
        $station = \App\Http\RequestHelper::getStation($request);

        /** @var Entity\User $user */
        $user = $request->getAttribute(Request::ATTRIBUTE_USER);

        if ($this->acl->userAllowed($user, Acl::GLOBAL_VIEW, $station->getId())) {
            return;
        }

        $auth_key = $request->getParam('api_auth');
        if (!$station->validateAdapterApiKey($auth_key)) {
            $this->logger->error('Invalid API key supplied for internal API call.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            throw new \App\Exception\PermissionDenied;
        }
    }
}
