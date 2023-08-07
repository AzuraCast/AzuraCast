<?php

declare(strict_types=1);

namespace App\Controller\Stations;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use Psr\Http\Message\ResponseInterface;

final class SftpUsersAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly AzuraCastCentral $acCentral
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $baseUrl = $request->getRouter()->getBaseUrl()
            ->withScheme('sftp')
            ->withPort(null);

        $port = $this->environment->getSftpPort();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Stations/SftpUsers',
            id: 'station-sftp-users',
            title: __('SFTP Users'),
            props: [
                'connectionInfo' => [
                    'url' => (string)$baseUrl,
                    'ip' => $this->acCentral->getIp(),
                    'port' => $port,
                ],
            ],
        );
    }
}
