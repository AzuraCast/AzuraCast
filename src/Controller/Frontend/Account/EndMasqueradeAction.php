<?php

namespace App\Controller\Frontend\Account;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class EndMasqueradeAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $auth = $request->getAuth();
        $auth->endMasquerade();

        return $response->withRedirect($request->getRouter()->named('admin:users:index'));
    }
}
