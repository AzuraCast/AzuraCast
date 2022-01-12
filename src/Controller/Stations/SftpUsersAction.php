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

class SftpUsersAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        AzuraCastCentral $acCentral
    ): ResponseInterface {
        $station = $request->getStation();

        if (!SftpGo::isSupportedForStation($station)) {
            throw new StationUnsupportedException(__('This feature is not currently supported on this station.'));
        }

        $baseUrl = $request->getRouter()->getBaseUrl()
            ->withScheme('sftp')
            ->withPort(null);

        $port = $environment->getSftpPort();

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_StationsSftpUsers',
            id: 'station-sftp-users',
            title: __('SFTP Users'),
            props: [
                'listUrl'        => (string)$router->fromHere('api:stations:sftp-users'),
                'connectionInfo' => [
                    'url'  => (string)$baseUrl,
                    'ip'   => $acCentral->getIp(),
                    'port' => $port,
                ],
            ],
        );
    }
}
