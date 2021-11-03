<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class DeleteTwoFactorAction extends UsersController
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $user->setTwoFactorSecret(null);
        $this->em->persist($user);
        $this->em->flush();

        return $response->withJson(Entity\Api\Status::updated());
    }
}
