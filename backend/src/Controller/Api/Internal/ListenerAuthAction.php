<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Enums\StationPermissions;
use App\Exception\Http\PermissionDeniedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Radio\Frontend\Blocklist\BlocklistParser;
use App\Utilities\Types;
use Psr\Http\Message\ResponseInterface;

final class ListenerAuthAction implements SingleActionInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly BlocklistParser $blocklistParser
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $acl = $request->getAcl();
        if (!$acl->isAllowed(StationPermissions::View, $station->id)) {
            $authKey = Types::stringOrNull($params['api_auth'], true)
                ?? Types::stringOrNull($request->getQueryParam('api_auth'), true);

            if (null === $authKey || !$station->validateAdapterApiKey($authKey)) {
                $this->logger->error(
                    'Invalid API key supplied for internal API call.',
                    [
                        'station_id' => $station->id,
                        'station_name' => $station->name,
                    ]
                );

                throw PermissionDeniedException::create($request);
            }
        }

        $station = $request->getStation();
        $listenerIp = Types::string($request->getParam('ip'));

        if ($this->blocklistParser->isAllowed($station, $listenerIp)) {
            return $response->withHeader('icecast-auth-user', '1');
        }

        return $response
            ->withHeader('icecast-auth-user', '0')
            ->withHeader('icecast-auth-message', 'geo-blocked');
    }
}
