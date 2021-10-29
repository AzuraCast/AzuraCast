<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class GetMeAction extends UsersController
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $return = $this->toArray($user, [
            AbstractNormalizer::GROUPS => [
                EntityGroupsInterface::GROUP_ID,
                EntityGroupsInterface::GROUP_GENERAL,
            ],
        ]);

        foreach ($user->getRoles() as $role) {
            $return['roles'][] = [
                'id'   => $role->getIdRequired(),
                'name' => $role->getName(),
            ];
        }

        return $response->withJson($return);
    }
}
