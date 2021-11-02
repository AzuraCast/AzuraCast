<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Avatar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

class GetMeAction extends UsersController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Avatar $avatar
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $return = $this->toArray($user, [
            AbstractNormalizer::GROUPS => [
                EntityGroupsInterface::GROUP_ID,
                EntityGroupsInterface::GROUP_GENERAL,
            ],
        ]);

        // Avatars
        $avatarService = $avatar->getAvatarService();

        $email = $user->getEmail();

        $return['avatar'] = [
            'url_32'       => $avatar->getAvatar($email, 32),
            'url_64'       => $avatar->getAvatar($email, 64),
            'url_128'      => $avatar->getAvatar($email, 128),
            'service_name' => $avatarService->getServiceName(),
            'service_url'  => $avatarService->getServiceUrl(),
        ];

        foreach ($user->getRoles() as $role) {
            $return['roles'][] = [
                'id'   => $role->getIdRequired(),
                'name' => $role->getName(),
            ];
        }

        return $response->withJson($return);
    }
}
