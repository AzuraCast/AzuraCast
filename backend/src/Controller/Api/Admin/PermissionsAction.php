<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\Permission;
use App\Entity\Api\Admin\Permissions;
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
        tags: ['Administration: Roles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    ref: '#/components/schemas/Api_Admin_Permissions'
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class PermissionsAction implements SingleActionInterface
{
    public function __construct(
        private readonly Acl $acl,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $rawPermissions = $this->acl->listPermissions();

        $callback = fn(string $id, string $name) => new Permission($id, $name);

        $globalPermissions = array_map(
            $callback,
            array_keys($rawPermissions['global']),
            array_values($rawPermissions['global'])
        );

        $stationPermissions = array_map(
            $callback,
            array_keys($rawPermissions['station']),
            array_values($rawPermissions['station'])
        );

        return $response->withJson(
            new Permissions(
                $globalPermissions,
                $stationPermissions
            )
        );
    }
}
