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
            new OA\Response(
                response: 200,
                description: 'Success' // TODO: Response Body
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class PermissionsAction
{
    public function __construct(
        private readonly Acl $acl,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response
    ): ResponseInterface {
        $permissions = [];
        foreach ($this->acl->listPermissions() as $group => $actions) {
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
