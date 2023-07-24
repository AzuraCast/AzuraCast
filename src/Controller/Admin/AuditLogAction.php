<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

final class AuditLogAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $router = $request->getRouter();

        return $request->getView()->renderVuePage(
            response: $response,
            component: 'Admin/AuditLog',
            id: 'admin-audit-log',
            title: __('Audit Log'),
            props: [
                'baseApiUrl' => $router->fromHere('api:admin:auditlog'),
            ]
        );
    }
}
