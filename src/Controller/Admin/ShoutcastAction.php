<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use App\Version;
use Psr\Http\Message\ResponseInterface;

class ShoutcastAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Version $version
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminShoutcast',
            id: 'admin-shoutcast',
            title: __('Install SHOUTcast 2 DNAS'),
            props: [
                'apiUrl' => (string)$router->named('api:admin:shoutcast'),
            ],
        );
    }
}
