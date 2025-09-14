<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Stations\Vue\StreamersProps;
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
        $backendConfig = $station->backend_config;

        $serverUrl = ($settings->getBaseUrlAsUri() ?? $request->getRouter()->getBaseUrl())->getHost();

        return $response->withJson(
            new StreamersProps(
                recordStreams: $backendConfig->record_streams,
                connectionServerUrl: $serverUrl,
                connectionStreamPort: $backendConfig->dj_port,
                connectionIp: $this->acCentral->getIp(),
                connectionDjMountPoint: $backendConfig->dj_mount_point,
            )
        );
    }
}
