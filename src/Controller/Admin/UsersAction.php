<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\Repository\RoleRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class UsersAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        RoleRepository $roleRepo
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminUsers',
            id: 'admin-users',
            title: __('Users'),
            props: [
                'listUrl' => (string)$router->fromHere('api:admin:users'),
                'roles' => $roleRepo->fetchSelect(),
            ]
        );
    }
}
