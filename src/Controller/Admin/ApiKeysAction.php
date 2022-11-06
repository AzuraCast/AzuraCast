<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class ApiKeysAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminApiKeys',
            id: 'admin-api-keys',
            title: __('API Keys'),
            props: [
                'apiUrl' => $router->named('api:admin:api-keys'),
            ],
        );
    }
}
