<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class GetTwoFactorAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();

        return $response->withJson([
            'two_factor_enabled' => !empty($user->getTwoFactorSecret()),
        ]);
    }
}
