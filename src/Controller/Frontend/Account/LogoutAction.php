<?php

namespace App\Controller\Frontend\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class LogoutAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $auth = $request->getAuth();
        $auth->logout();

        return $response->withRedirect($request->getRouter()->named('account:login'));
    }
}
