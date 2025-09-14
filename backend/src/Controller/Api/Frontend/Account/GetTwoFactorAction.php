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
        summary: 'Get the current two-factor authentication status of your account.',
        tags: [OpenApi::TAG_ACCOUNTS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: TwoFactorStatus::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
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
                !empty($user->two_factor_secret)
            )
        );
    }
}
