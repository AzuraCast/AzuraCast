<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class BackupsAction
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
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
                    'group' => Settings::GROUP_BACKUP,
                ]),
                'isDocker' => $this->environment->isDocker(),
                'storageLocations' => $this->storageLocationRepo->fetchSelectByType(
                    StorageLocationTypes::Backup
                ),
            ],
        );
    }
}
