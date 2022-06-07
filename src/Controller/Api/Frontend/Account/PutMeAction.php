<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Entity;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

final class PutMeAction extends UsersController
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $this->editRecord(
            (array)$request->getParsedBody(),
            $user,
            [
                AbstractNormalizer::GROUPS => [
                    EntityGroupsInterface::GROUP_ID,
                    EntityGroupsInterface::GROUP_GENERAL,
                ],
            ]
        );

        return $response->withJson(Entity\Api\Status::updated());
    }
}
