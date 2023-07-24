<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Entity\Repository\RoleRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class UsersAction implements SingleActionInterface
{
    public function __construct(
        private readonly RoleRepository $roleRepo,
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
            component: 'Admin/Users',
            id: 'admin-users',
            title: __('Users'),
            props: [
                'listUrl' => $router->fromHere('api:admin:users'),
                'roles' => $this->roleRepo->fetchSelect(),
            ]
        );
    }
}
