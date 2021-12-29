<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/permissions',
        operationId: 'getPermissions',
        description: 'Return a list of all available permissions.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Roles'],
        responses: [
            new OA\Response(response: 200, description: 'Success'),
            new OA\Response(response: 403, description: 'Access denied'),
        ]
    )
]
class PermissionsController
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        Acl $acl
    ): ResponseInterface {
        $permissions = [];
        foreach ($acl->listPermissions() as $group => $actions) {
            foreach ($actions as $action_id => $action_name) {
                $permissions[$group][] = [
                    'id' => $action_id,
                    'name' => $action_name,
                ];
            }
        }

        return $response->withJson($permissions);
    }
}
