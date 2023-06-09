<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class RelaysAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminRelays',
            id: 'admin-relays',
            title: __('Connected Relays'),
            props: [
                'listUrl' => $router->fromHere('api:admin:relays'),
            ]
        );
    }
}
