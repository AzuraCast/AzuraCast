<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use Psr\Http\Message\ResponseInterface;

final class StreamersAction implements SingleActionInterface
{
    use SettingsAwareTrait;

    public function __construct(
        private readonly AzuraCastCentral $acCentral,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $station = $request->getStation();

        $settings = $this->readSettings();
        $backendConfig = $station->getBackendConfig();

        return $response->withJson([
            'connectionInfo' => [
                'serverUrl' => $settings->getBaseUrl(),
                'streamPort' => $backendConfig->getDjPort(),
                'ip' => $this->acCentral->getIp(),
                'djMountPoint' => $backendConfig->getDjMountPoint(),
            ],
        ]);
    }
}
