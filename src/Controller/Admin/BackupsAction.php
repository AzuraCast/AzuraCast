<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class BackupsAction
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly Entity\Repository\StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminBackups',
            id: 'admin-backups',
            title: __('Backups'),
            props: [
                'listUrl' => $router->named('api:admin:backups'),
                'runBackupUrl' => $router->named('api:admin:backups:run'),
                'settingsUrl' => $router->named('api:admin:settings', [
                    'group' => Entity\Settings::GROUP_BACKUP,
                ]),
                'isDocker' => $this->environment->isDocker(),
                'storageLocations' => $this->storageLocationRepo->fetchSelectByType(
                    Entity\Enums\StorageLocationTypes::Backup
                ),
            ],
        );
    }
}
