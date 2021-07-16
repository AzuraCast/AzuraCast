<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Acl;
use App\Exception\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Sync\Task\NowPlayingTask;
use Monolog\Logger;
use Psr\Http\Message\ResponseInterface;

class InternalController
{
    public function __construct(
        protected NowPlayingTask $syncNowPlaying,
        protected AutoDJ $autodj,
        protected Logger $logger
    ) {
    }

    public function authAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkStationAuth($request);

        $station = $request->getStation();
        if (!$station->getEnableStreamers()) {
            $this->logger->error(
                'Attempted DJ authentication when streamers are disabled on this station.',
                [
                    'station_id' => $station->getId(),
                    'station_name' => $station->getName(),
                ]
            );

            $response->getBody()->write('false');
            return $response;
        }

        $params = $request->getParams();
        $user = $params['dj-user'] ?? '';
        $pass = $params['dj-password'] ?? '';

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $response->getBody()->write($adapter->authenticateStreamer($station, $user, $pass));
            return $response;
        }

        $response->getBody()->write('false');
        return $response;
    }

    protected function checkStationAuth(ServerRequest $request): void
    {
        $station = $request->getStation();

        $acl = $request->getAcl();
        if ($acl->isAllowed(Acl::GLOBAL_VIEW, $station->getId())) {
            return;
        }

        $params = $request->getParams();
        $auth_key = $params['api_auth'];
        if (!$station->validateAdapterApiKey($auth_key)) {
            $this->logger->error(
                'Invalid API key supplied for internal API call.',
                [
                    'station_id' => $station->getId(),
                    'station_name' => $station->getName(),
                ]
            );

            throw new PermissionDeniedException();
        }
    }

    public function nextsongAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkStationAuth($request);

        $params = $request->getParams();
        $as_autodj = isset($params['api_auth']);

        $response->getBody()->write($this->autodj->annotateNextSong($request->getStation(), $as_autodj));
        return $response;
    }

    public function djonAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkStationAuth($request);

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $station = $request->getStation();
            $user = $request->getParam('dj-user', '');

            $this->logger->notice(
                'Received "DJ connected" ping from Liquidsoap.',
                [
                    'station_id' => $station->getId(),
                    'station_name' => $station->getName(),
                    'dj' => $user,
                ]
            );

            $response->getBody()->write($adapter->onConnect($station, $user));
            return $response;
        }

        $response->getBody()->write('received');
        return $response;
    }

    public function djoffAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkStationAuth($request);

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $station = $request->getStation();
            $user = $request->getParam('dj-user', '');

            $this->logger->notice(
                'Received "DJ disconnected" ping from Liquidsoap.',
                [
                    'station_id' => $station->getId(),
                    'station_name' => $station->getName(),
                    'dj' => $user,
                ]
            );

            $response->getBody()->write($adapter->onDisconnect($station, $user));
            return $response;
        }

        $response->getBody()->write('received');
        return $response;
    }

    public function feedbackAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkStationAuth($request);

        $station = $request->getStation();

        $body = $request->getParams();

        $this->syncNowPlaying->queueStation(
            $station,
            [
                'song_id' => $body['song'] ?? null,
                'media_id' => $body['media'] ?? null,
                'playlist_id' => $body['playlist'] ?? null,
            ]
        );

        $response->getBody()->write('OK');
        return $response;
    }
}
