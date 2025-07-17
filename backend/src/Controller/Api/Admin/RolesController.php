<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Api\Admin\Role as ApiRole;
use App\Entity\Repository\RolePermissionRepository;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<Role> */
#[
    OA\Get(
        path: '/admin/roles',
        operationId: 'getRoles',
        summary: 'List all current roles in the system.',
        tags: [OpenApi::TAG_ADMIN_ROLES],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        ref: ApiRole::class
                    )
                )
            ),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/admin/roles',
        operationId: 'addRole',
        summary: 'Create a new role.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiRole::class)
        ),
        tags: [OpenApi::TAG_ADMIN_ROLES],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: ApiRole::class
                )
            ),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/role/{id}',
        operationId: 'getRole',
        summary: 'Retrieve details for a single current role.',
        tags: [OpenApi::TAG_ADMIN_ROLES],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Role ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: ApiRole::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/admin/role/{id}',
        operationId: 'editRole',
        summary: 'Update details of a single role.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: ApiRole::class)
        ),
        tags: [OpenApi::TAG_ADMIN_ROLES],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Role ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/admin/role/{id}',
        operationId: 'deleteRole',
        summary: 'Delete a single role.',
        tags: [OpenApi::TAG_ADMIN_ROLES],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Role ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class RolesController extends AbstractApiCrudController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = Role::class;
    protected string $resourceRouteName = 'api:admin:role';

    private readonly Role $superAdminRole;

    public function __construct(
        Serializer $serializer,
        ValidatorInterface $validator,
        RolePermissionRepository $permissionRepo,
        private readonly Acl $acl,
    ) {
        parent::__construct($serializer, $validator);

        $this->superAdminRole = $permissionRepo->ensureSuperAdministratorRole();
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $qb = $this->em->createQueryBuilder()
            ->select('r, rp')
            ->from(Role::class, 'r')
            ->leftJoin('r.permissions', 'rp');

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'name' => 'r.name',
            ],
            'r.name'
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'r.name',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): ApiRole
    {
        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $apiRole = ApiRole::fromRole($record);
        $apiRole->is_super_admin = $record->id === $this->superAdminRole->id;
        $apiRole->links = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
        ];

        return $apiRole;
    }

    protected function editRecord(?array $data, ?object $record = null, array $context = []): object
    {
        if (
            null !== $record
            && $this->superAdminRole->id === $record->id
        ) {
            throw new RuntimeException('Cannot modify the Super Administrator role.');
        }

        return parent::editRecord($data, $record, $context);
    }

    protected function deleteRecord(object $record): void
    {
        if ($this->superAdminRole->id === $record->id) {
            throw new RuntimeException('Cannot remove the Super Administrator role.');
        }

        parent::deleteRecord($record);
    }

    protected function fromArray(array $data, $record = null, array $context = []): object
    {
        $permissions = null;
        if (isset($data['permissions'])) {
            $permissions = (array)$data['permissions'];
            unset($data['permissions']);
        }

        $record = parent::fromArray($data, $record, $context);

        if (null !== $permissions) {
            $this->doUpdatePermissions($record, $permissions);
        }

        return $record;
    }

    private function doUpdatePermissions(Role $role, array $newPermissions): void
    {
        $existingPerms = $role->permissions;
        if ($existingPerms->count() > 0) {
            foreach ($existingPerms as $perm) {
                $this->em->remove($perm);
            }
            $this->em->flush();
            $existingPerms->clear();
        }

        if (isset($newPermissions['global'])) {
            foreach ($newPermissions['global'] as $permName) {
                if ($this->acl->isValidPermission($permName, true)) {
                    $permRecord = new RolePermission($role, null, $permName);
                    $this->em->persist($permRecord);
                }
            }
        }

        if (isset($newPermissions['station'])) {
            foreach ($newPermissions['station'] as $stationId => $stationPerms) {
                // Accept both { id: perms[] } and [ { id: 1, perms: string[] } ] formats.
                if (isset($stationPerms['id'])) {
                    $stationId = $stationPerms['id'];
                    $stationPerms = $stationPerms['permissions'] ?? [];
                }

                $station = $this->em->find(Station::class, $stationId);

                if ($station instanceof Station) {
                    foreach ($stationPerms as $permName) {
                        if ($this->acl->isValidPermission($permName, false)) {
                            $permRecord = new RolePermission($role, $station, $permName);
                            $this->em->persist($permRecord);
                        }
                    }
                }
            }
        }
    }
}
