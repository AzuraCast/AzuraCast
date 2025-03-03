<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Container\EntityManagerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Account\TwoFactorStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/frontend/account/two-factor',
        operationId: 'getMyTwoFactor',
        description: 'Get the current two-factor authentication status of your account.',
        tags: ['Accounts'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    ref: TwoFactorStatus::class
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class GetTwoFactorAction implements SingleActionInterface
{
    use EntityManagerAwareTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $user = $request->getUser();
        $user = $this->em->refetch($user);

        return $response->withJson(
            new TwoFactorStatus(
                !empty($user->getTwoFactorSecret())
            )
        );
    }
}
