<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Enums\StationPermissions;
use App\Enums\SupportedLocales;
use App\Exception\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\AutoDJ;
use App\Radio\Backend\Liquidsoap;
use App\Service\IpGeolocation;
use InvalidArgumentException;
use Monolog\Logger;
use PhpIP\IP;
use PhpIP\IPBlock;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Intl\Countries;

class InternalController
{
    public function __construct(
        protected Liquidsoap\Feedback $feedback,
        protected AutoDJ\Annotations $annotations,
        protected Logger $logger,
        protected IpGeolocation $ipGeolocation
    ) {
    }

    public function authAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkLiquidsoapAuth($request);

        $station = $request->getStation();
        if ($station->getEnableStreamers()) {
            $params = (array)$request->getParsedBody();
            $user = $params['user'] ?? '';
            $pass = $params['password'] ?? '';

            $adapter = $request->getStationBackend();
            if (($adapter instanceof Liquidsoap) && $adapter->authenticateStreamer($station, $user, $pass)) {
                $response->getBody()->write('true');
                return $response->withStatus(200);
            }
        } else {
            $this->logger->error(
                'Attempted DJ authentication when streamers are disabled on this station.',
                [
                    'station_id'   => $station->getId(),
                    'station_name' => $station->getName(),
                ]
            );
        }

        $response->getBody()->write('false');
        return $response->withStatus(403);
    }

    public function nextsongAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkLiquidsoapAuth($request);

        $response->getBody()->write(
            $this->annotations->annotateNextSong(
                $request->getStation(),
                $request->hasHeader('X-Liquidsoap-Api-Key')
            )
        );
        return $response;
    }

    public function djonAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $this->checkLiquidsoapAuth($request);

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $station = $request->getStation();
            $user = $request->getParsedBodyParam('user', '');

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
        $this->checkLiquidsoapAuth($request);

        $adapter = $request->getStationBackend();
        if ($adapter instanceof Liquidsoap) {
            $station = $request->getStation();
            $user = $request->getParsedBodyParam('user', '');

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
        $this->checkLiquidsoapAuth($request);

        ($this->feedback)(
            $request->getStation(),
            (array)$request->getParsedBody()
        );

        $response->getBody()->write('OK');
        return $response;
    }

    protected function checkLiquidsoapAuth(ServerRequest $request): void
    {
        $station = $request->getStation();

        $acl = $request->getAcl();
        if ($acl->isAllowed(StationPermissions::View, $station->getId())) {
            return;
        }

        $authKey = $request->getHeaderLine('X-Liquidsoap-Api-Key');
        if (!$station->validateAdapterApiKey($authKey)) {
            $this->logger->error(
                'Invalid API key supplied for internal API call.',
                [
                    'station_id'   => $station->getId(),
                    'station_name' => $station->getName(),
                ]
            );

            throw new PermissionDeniedException();
        }
    }

    public function listenerAuthAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $station = $request->getStation();

        $acl = $request->getAcl();
        if (!$acl->isAllowed(StationPermissions::View, $station->getId())) {
            $authKey = $request->getQueryParam('api_auth', '');
            if (!$station->validateAdapterApiKey($authKey)) {
                $this->logger->error(
                    'Invalid API key supplied for internal API call.',
                    [
                        'station_id'   => $station->getId(),
                        'station_name' => $station->getName(),
                    ]
                );

                throw new PermissionDeniedException();
            }
        }

        $station = $request->getStation();
        $frontendConfig = $station->getFrontendConfig();

        $bannedCountries = $frontendConfig->getBannedCountries() ?? [];
        if (empty($bannedCountries)) {
            return $response->withHeader('icecast-auth-user', '1');
        }

        $listenerIp = $request->getParam('ip') ?? '';
        $listenerLocation = $this->ipGeolocation->getLocationInfo($listenerIp, SupportedLocales::default());

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

            $countries = Countries::getNames(SupportedLocales::default()->value);

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
