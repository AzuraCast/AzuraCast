<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Container\EnvironmentAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Enums\StorageLocationTypes;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\Settings;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class BackupsAction implements SingleActionInterface
{
    use EnvironmentAwareTrait;

    public function __construct(
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/Backups',
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
