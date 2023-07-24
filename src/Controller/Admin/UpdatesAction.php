<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;

final class UpdatesAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly Version $version
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $settings = $this->readSettings();

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/Updates',
            id: 'admin-updates',
            title: __('Update AzuraCast'),
            props: [
                'releaseChannel' => $this->version->getReleaseChannelEnum()->value,
                'initialUpdateInfo' => $settings->getUpdateResults(),
                'backupUrl' => $router->named('admin:backups:index'),
                'updatesApiUrl' => $router->named('api:admin:updates'),
                'enableWebUpdates' => $this->environment->enableWebUpdater(),
            ],
        );
    }
}
