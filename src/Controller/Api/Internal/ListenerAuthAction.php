<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Enums\StationPermissions;
use App\Exception\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\Blocklist\BlocklistParser;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class ListenerAuthAction
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly BlocklistParser $blocklistParser
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        $acl = $request->getAcl();
        if (!$acl->isAllowed(StationPermissions::View, $station->getId())) {
            $authKey = $request->getQueryParam('api_auth', '');
            if (!$station->validateAdapterApiKey($authKey)) {
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

        $station = $request->getStation();
        $listenerIp = $request->getParam('ip') ?? '';

        if (
            $this->blocklistParser->isIpExplicitlyAllowed($listenerIp, $station)
            || !$this->blocklistParser->isCountryBanned($listenerIp, $station)
        ) {
            return $response->withHeader('icecast-auth-user', '1');
        }

        return $response
            ->withHeader('icecast-auth-user', '0')
            ->withHeader('icecast-auth-message', 'geo-blocked');
    }
}
