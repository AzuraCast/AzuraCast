<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
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
        string $station_id
    ): ResponseInterface {
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
                'listUrl' => $router->fromHere('api:stations:sftp-users'),
                'connectionInfo' => [
                    'url' => (string)$baseUrl,
                    'ip' => $this->acCentral->getIp(),
                    'port' => $port,
                ],
            ],
        );
    }
}
