<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;

final class ShoutcastAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        if ('x86_64' !== php_uname('m')) {
            throw new RuntimeException('Shoutcast cannot be installed on non-X86_64 systems.');
        }

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/Shoutcast',
            id: 'admin-shoutcast',
            title: __('Install Shoutcast 2 DNAS'),
        );
    }
}
