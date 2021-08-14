<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Acl;
use App\Exception\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Locale;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Service\IpGeolocation;
use App\Sync\Task\NowPlayingTask;
use InvalidArgumentException;
use Monolog\Logger;
use PhpIP\IP;
use PhpIP\IPBlock;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Countries;

class InternalController
{
    public function __construct(
        protected NowPlayingTask $syncNowPlaying,
        protected AutoDJ $autodj,
        protected Logger $logger,
        protected IpGeolocation $ipGeolocation
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

    public function listenerAuthAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkStationAuth($request);

        $station = $request->getStation();
        $frontendConfig = $station->getFrontendConfig();

        $bannedCountries = $frontendConfig->getBannedCountries() ?? [];
        if (empty($bannedCountries)) {
            return $response->withHeader('icecast-auth-user', '1');
        }

        $listenerIp = $request->getParam('ip') ?? '';
        $listenerLocation = $this->ipGeolocation->getLocationInfo($listenerIp, Locale::DEFAULT_LOCALE);

        $allowedIps = $frontendConfig->getAllowedIps();
        if (!empty($allowedIps)) {
            foreach (array_filter(array_map('trim', explode("\n", $allowedIps))) as $ip) {
                try {
                    if (!str_contains($ip, '/')) {
                        $ipObj = IP::create($ip);
                        if ($ipObj->matches($listenerIp)) {
                            return $response->withHeader('icecast-auth-user', '1');
                        }
                    } else {
                        // Iterate through CIDR notation
                        foreach (IPBlock::create($ip) as $ipObj) {
                            if ($ipObj->matches($listenerIp)) {
                                return $response->withHeader('icecast-auth-user', '1');
                            }
                        }
                    }
                } catch (InvalidArgumentException) {
                }
            }
        }

        if ('success' === $listenerLocation['status']) {
            $listenerCountry = $listenerLocation['country'];

            $countries = Countries::getNames(Locale::DEFAULT_LOCALE);

            $listenerCountryCode = '';
            foreach ($countries as $countryCode => $countryName) {
                if ($countryName === $listenerCountry) {
                    $listenerCountryCode = $countryCode;
                    break;
                }
            }

            foreach ($bannedCountries as $countryCode) {
                if ($countryCode === $listenerCountryCode) {
                    return $response
                        ->withHeader('icecast-auth-user', '0')
                        ->withHeader('icecast-auth-message', 'geo-blocked');
                }
            }

            return $response->withHeader('icecast-auth-user', '1');
        }

        if ('Internal/Reserved IP' === $listenerLocation['message']) {
            return $response->withHeader('icecast-auth-user', '1');
        }

        return $response
            ->withHeader('icecast-auth-user', '0')
            ->withHeader('icecast-auth-message', 'geo-blocked');
    }
}
