<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DeleteTwoFactorAction
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $user->setTwoFactorSecret();
        $this->em->persist($user);
        $this->em->flush();

        return $response->withJson(Status::updated());
    }
}
