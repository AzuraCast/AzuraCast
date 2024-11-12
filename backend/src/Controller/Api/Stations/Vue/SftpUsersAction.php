<?php

declare(strict_types=1);

namespace App\Controller\Api\Stations\Vue;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\AzuraCastCentral;
use Psr\Http\Message\ResponseInterface;

final class SftpUsersAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly AzuraCastCentral $acCentral
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $this->settingsRepo->readSettings();

        $baseUrl = ($settings->getBaseUrlAsUri() ?? $request->getRouter()->getBaseUrl())
            ->withScheme('sftp')
            ->withPort(null);

        $port = $this->environment->getSftpPort();

        return $response->withJson([
            'connectionInfo' => [
                'url' => (string)$baseUrl,
                'ip' => $this->acCentral->getIp(),
                'port' => $port,
            ],
        ]);
    }
}
