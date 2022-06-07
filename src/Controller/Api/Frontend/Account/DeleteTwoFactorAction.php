<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class DeleteTwoFactorAction
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $user->setTwoFactorSecret();
        $this->em->persist($user);
        $this->em->flush();

        return $response->withJson(Entity\Api\Status::updated());
    }
}
