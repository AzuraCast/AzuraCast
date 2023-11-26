<?php

declare(strict_types=1);

namespace App\Controller\Frontend\Account\WebAuthn;

use App\Controller\Traits\UsesWebAuthnTrait;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetValidationAction
{
    use UsesWebAuthnTrait;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $webAuthn = $this->getWebAuthn($request);

        $getArgs = $webAuthn->getGetArgs(
            [],
            self::WEBAUTHN_TIMEOUT
        );

        $this->setChallenge($request, $webAuthn->getChallenge());

        return $response->withJson($getArgs);
    }
}
