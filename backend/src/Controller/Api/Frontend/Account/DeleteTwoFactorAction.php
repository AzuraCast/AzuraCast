<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Delete(
        path: '/frontend/account/two-factor',
        operationId: 'deleteMyTwoFactor',
        description: 'Remove two-factor authentication from your account.',
        tags: [OpenApi::TAG_ACCOUNTS],
        responses: [
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class DeleteTwoFactorAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        $user->setTwoFactorSecret();
        $this->em->persist($user);
        $this->em->flush();

        return $response->withJson(Status::updated());
    }
}
