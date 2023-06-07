<?php

namespace App\Controller\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Repository\SettingsRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;

final class UpdatesAction
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly SettingsRepository $settingsRepo,
        private readonly Version $version
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $settings = $this->settingsRepo->readSettings();

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminUpdates',
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
