<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Repository\StationRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class PermissionsAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        StationRepository $stationRepo
    ): ResponseInterface {
        $router = $request->getRouter();

        $actions = $request->getAcl()->listPermissions();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Roles & Permissions'),
                'id' => 'admin-permissions',
                'component' => 'Vue_AdminPermissions',
                'props' => [
                    'listUrl' => (string)$router->fromHere('api:admin:roles'),
                    'stations' => $stationRepo->fetchSelect(),
                    'globalPermissions' => $actions['global'],
                    'stationPermissions' => $actions['station'],
                ],
            ]
        );
    }
}
