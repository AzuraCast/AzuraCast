<?php
namespace App\Controller\Api;

use App\Acl;
use App\Entity;
use App\Http\RequestHelper;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task\NowPlaying;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

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

    /**
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function authAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $station = RequestHelper::getStation($request);
        if (!$station->getEnableStreamers()) {
            $this->logger->error('Attempted DJ authentication when streamers are disabled on this station.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            $response->getBody()->write('false');
            return $response;
        }

        $params = $request->getQueryParams();

        $user = $params['dj_user'] ?? '';
        $pass = $params['dj_password'] ?? '';

        $adapter = RequestHelper::getStationBackend($request);
        if ($adapter instanceof Liquidsoap) {
            $response->getBody()->write($adapter->authenticateStreamer($station, $user, $pass));
            return $response;
        }

        $response->getBody()->write('false');
        return $response;
    }

    public function nextsongAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $params = $request->getQueryParams();
        $as_autodj = isset($params['api_auth']);

        $response->getBody()->write($this->autodj->annotateNextSong(RequestHelper::getStation($request), $as_autodj));
        return $response;
    }

    public function djonAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $adapter = RequestHelper::getStationBackend($request);
        if ($adapter instanceof Liquidsoap) {
            $station = RequestHelper::getStation($request);

            $this->logger->info('Received "DJ connected" ping from Liquidsoap.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            $adapter->toggleLiveStatus($station, true);
        }

        $response->getBody()->write('received');
        return $response;
    }

    public function djoffAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $adapter = RequestHelper::getStationBackend($request);
        if ($adapter instanceof Liquidsoap) {
            $station = RequestHelper::getStation($request);

            $this->logger->info('Received "DJ disconnected" ping from Liquidsoap.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            $adapter->toggleLiveStatus($station, false);
        }

        $response->getBody()->write('received');
        return $response;
    }

    public function feedbackAction(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $this->_checkStationAuth($request);

        $station = RequestHelper::getStation($request);

        $body = $request->getParsedBody();

        $this->sync_nowplaying->queueStation($station, [
            'song_id'   => $body['song'] ?? null,
            'media_id'  => $body['media'] ?? null,
            'playlist_id'  => $body['playlist'] ?? null,
        ]);

        $response->getBody()->write('OK');
        return $response;
    }

    /**
     * @param ServerRequestInterface $request
     */
    protected function _checkStationAuth(ServerRequestInterface $request): void
    {
        $station = RequestHelper::getStation($request);

        /** @var Entity\User $user */
        $user = $request->getAttribute(RequestHelper::ATTR_USER);

        if ($this->acl->userAllowed($user, Acl::GLOBAL_VIEW, $station->getId())) {
            return;
        }

        $params = $request->getQueryParams();
        $auth_key = $params['api_auth'];
        if (!$station->validateAdapterApiKey($auth_key)) {
            $this->logger->error('Invalid API key supplied for internal API call.', [
                'station_id' => $station->getId(),
                'station_name' => $station->getName(),
            ]);

            throw new \App\Exception\PermissionDenied;
        }
    }
}
