<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Admin\UsersController;
use App\Controller\SingleActionInterface;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Service\Avatar;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class GetMeAction extends UsersController implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly Avatar $avatar,
        Serializer $serializer,
        ValidatorInterface $validator,
    ) {
        parent::__construct($serializer, $validator);
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
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
        $avatarService = $this->avatar->getAvatarService();

        $email = $user->getEmail();

        $return['roles'] = [];

        $return['avatar'] = [
            'url_32' => $this->avatar->getAvatar($email, 32),
            'url_64' => $this->avatar->getAvatar($email, 64),
            'url_128' => $this->avatar->getAvatar($email, 128),
            'service_name' => $avatarService->getServiceName(),
            'service_url' => $avatarService->getServiceUrl(),
        ];

        foreach ($user->getRoles() as $role) {
            $return['roles'][] = [
                'id' => $role->getIdRequired(),
                'name' => $role->getName(),
            ];
        }

        return $response->withJson($return);
    }
}
