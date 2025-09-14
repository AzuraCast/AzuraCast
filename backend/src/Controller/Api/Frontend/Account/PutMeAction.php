<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Entity\Interfaces\EntityGroupsInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[
    OA\Put(
        path: '/frontend/account/me',
        operationId: 'putMe',
        summary: 'Save changes to your logged in account.',
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
final class PutMeAction extends UsersController implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
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

        return $response->withJson(Status::updated());
    }
}
