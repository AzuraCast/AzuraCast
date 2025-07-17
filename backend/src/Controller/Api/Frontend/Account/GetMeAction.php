<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Controller\Api\Admin\UsersController;
use App\Controller\SingleActionInterface;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\Avatar;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[
    OA\Get(
        path: '/frontend/account/me',
        operationId: 'getMe',
        summary: 'Show the details for your current logged-in account.',
        tags: [OpenApi::TAG_ACCOUNTS],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
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

        $email = $user->email;

        $return['roles'] = [];

        $return['avatar'] = [
            'url_32' => $this->avatar->getAvatar($email, 32),
            'url_64' => $this->avatar->getAvatar($email, 64),
            'url_128' => $this->avatar->getAvatar($email, 128),
            'service_name' => $avatarService->getServiceName(),
            'service_url' => $avatarService->getServiceUrl(),
        ];

        foreach ($user->roles as $role) {
            $return['roles'][] = [
                'id' => $role->id,
                'name' => $role->name,
            ];
        }

        return $response->withJson($return);
    }
}
