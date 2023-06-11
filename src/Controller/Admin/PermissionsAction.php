<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class PermissionsAction implements SingleActionInterface
{
    public function __construct(
        private readonly StationRepository $stationRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        $actions = $request->getAcl()->listPermissions();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminPermissions',
            id: 'admin-permissions',
            title: __('Roles & Permissions'),
            props: [
                'listUrl' => $router->fromHere('api:admin:roles'),
                'stations' => $this->stationRepo->fetchSelect(),
                'globalPermissions' => $actions['global'],
                'stationPermissions' => $actions['station'],
            ]
        );
    }
}
