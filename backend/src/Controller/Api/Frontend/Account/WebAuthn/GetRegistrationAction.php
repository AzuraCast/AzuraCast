<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account\WebAuthn;

use App\Controller\Traits\UsesWebAuthnTrait;
use App\Entity\Repository\UserPasskeyRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/frontend/account/webauthn/register',
        operationId: 'getAccountWebAuthnRegister',
        summary: 'Get registration details for WebAuthn registration.',
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
final class GetRegistrationAction
{
    use UsesWebAuthnTrait;

    public function __construct(
        private readonly UserPasskeyRepository $passkeyRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $user = $request->getUser();
        $webAuthn = $this->getWebAuthn($request);

        $createArgs = $webAuthn->getCreateArgs(
            (string)$user->id,
            $user->email,
            $user->getDisplayName(),
            self::WEBAUTHN_TIMEOUT,
            requireResidentKey: 'required',
            requireUserVerification: 'preferred',
            excludeCredentialIds: $this->passkeyRepo->getCredentialIds($user),
        );

        $this->setChallenge($request, $webAuthn->getChallenge());

        return $response->withJson($createArgs);
    }
}
