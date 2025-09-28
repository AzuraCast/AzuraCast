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
        summary: 'Return a list of all available permissions.',
        tags: [OpenApi::TAG_ADMIN_ROLES],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: Permissions::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final readonly class PermissionsAction implements SingleActionInterface
{
    public function __construct(
        private Acl $acl,
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
