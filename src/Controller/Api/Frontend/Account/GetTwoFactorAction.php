<?php

declare(strict_types=1);

namespace App\Controller\Api\Frontend\Account;

use App\Controller\Api\Admin\UsersController;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class GetTwoFactorAction extends UsersController
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $user = $request->getUser();

        return $response->withJson([
            'twoFactorEnabled' => !empty($user->getTwoFactorSecret()),
        ]);
    }
}
