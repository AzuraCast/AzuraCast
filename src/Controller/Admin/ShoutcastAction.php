<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ShoutcastAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        if ('x86_64' !== php_uname('m')) {
            throw new RuntimeException('Shoutcast cannot be installed on non-X86_64 systems.');
        }

        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Vue_AdminShoutcast',
            id: 'admin-shoutcast',
            title: __('Install Shoutcast 2 DNAS'),
            props: [
                'apiUrl' => $router->named('api:admin:shoutcast'),
            ],
        );
    }
}
