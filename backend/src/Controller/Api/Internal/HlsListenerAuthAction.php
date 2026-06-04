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
use Throwable;

final class HlsListenerAuthAction implements SingleActionInterface
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

        try {
            $listenerIp = $this->getListenerIp($request);
            $userAgent = Types::stringOrNull($request->getHeaderLine('User-Agent'), true);

            $isAllowed = $this->blocklistParser->isAllowed($station, $listenerIp, $userAgent);
        } catch (Throwable $exception) {
            $this->logger->warning(
                'Error during HLS listener authentication; allowing listener.',
                [
                    'station_id' => $station->id,
                    'exception' => $exception,
                ]
            );

            return $response->withStatus(200);
        }

        return $response->withStatus($isAllowed ? 200 : 403);
    }

    private function getListenerIp(ServerRequest $request): string
    {
        $ip = $request->getSettings()->getIp($request);

        if ($this->isLoopbackIp($ip)) {
            $realIp = Types::stringOrNull($request->getHeaderLine('X-Real-IP'), true);
            if (null !== $realIp) {
                return $realIp;
            }
        }

        return $ip;
    }

    private function isLoopbackIp(string $ip): bool
    {
        return '::1' === $ip || str_starts_with($ip, '127.');
    }
}
