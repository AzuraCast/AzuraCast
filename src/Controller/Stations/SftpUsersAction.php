<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Environment;
use App\Exception\StationUnsupportedException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use App\Service\SftpGo;
use Psr\Http\Message\ResponseInterface;

final class SftpUsersAction
{
    public function __construct(
        private readonly Environment $environment,
        private readonly AzuraCastCentral $acCentral
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        int|string $station_id
    ): ResponseInterface {
        $station = $request->getStation();

        if (!SftpGo::isSupportedForStation($station)) {
            throw new StationUnsupportedException(__('This feature is not currently supported on this station.'));
        }

        $baseUrl = $request->getRouter()->getBaseUrl()
            ->withScheme('sftp')
            ->withPort(null);

        $port = $this->environment->getSftpPort();

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsSftpUsers',
            id: 'station-sftp-users',
            title: __('SFTP Users'),
            props: [
                'listUrl' => (string)$router->fromHere('api:stations:sftp-users'),
                'connectionInfo' => [
                    'url' => (string)$baseUrl,
                    'ip' => $this->acCentral->getIp(),
                    'port' => $port,
                ],
            ],
        );
    }
}
