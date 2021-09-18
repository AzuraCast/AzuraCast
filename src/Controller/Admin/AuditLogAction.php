<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Http\Response;
use App\Http\ServerRequest;
use Psr\Http\Message\ResponseInterface;

class AuditLogAction
{
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $router = $request->getRouter();

        return $request->getView()->renderToResponse(
            $response,
            'system/vue',
            [
                'title' => __('Audit Log'),
                'id' => 'admin-audit-log',
                'component' => 'Vue_AdminAuditLog',
                'props' => [
                    'baseApiUrl' => (string)$router->fromHere('api:admin:auditlog'),
                ],
            ]
        );
    }
}
