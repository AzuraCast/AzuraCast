<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class IndexAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin',
            id: 'admin-index',
            title: __('Administration'),
            props: [
                'baseUrl' => $request->getRouter()->named('admin:index:index'),
            ]
        );
    }
}
