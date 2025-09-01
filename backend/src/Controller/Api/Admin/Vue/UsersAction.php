<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Vue;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Vue\UsersProps;
use App\Entity\Repository\RoleRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final readonly class UsersAction implements SingleActionInterface
{
    public function __construct(
        private RoleRepository $roleRepo,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            new UsersProps(
                roles: $this->roleRepo->fetchSelect()
            )
        );
    }
}
