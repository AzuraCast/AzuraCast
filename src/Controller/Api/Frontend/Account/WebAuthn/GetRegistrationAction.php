<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account\WebAuthn;

use App\Controller\Traits\UsesWebAuthnTrait;
use App\Entity\Repository\UserPasskeyRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

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
            (string)$user->getId(),
            $user->getEmail(),
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
