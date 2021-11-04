<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity;
use App\Environment;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class BackupsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Environment $environment,
        Entity\Repository\StorageLocationRepository $storageLocationRepo
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminBackups',
            id: 'admin-backups',
            title: __('Backups'),
            props: [
                'listUrl'          => (string)$router->named('api:admin:backups'),
                'runBackupUrl'     => (string)$router->named('api:admin:backups:run'),
                'settingsUrl'      => (string)$router->named('api:admin:settings', [
                    'group' => Entity\Settings::GROUP_BACKUP,
                ]),
                'isDocker'         => $environment->isDocker(),
                'storageLocations' => $storageLocationRepo->fetchSelectByType(Entity\StorageLocation::TYPE_BACKUP),
            ],
        );
    }
}
