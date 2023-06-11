<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSortResults;
use App\Entity\Repository\RolePermissionRepository;
use App\Entity\Role;
use App\Entity\RolePermission;
use App\Entity\Station;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
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
        description: 'List all current roles in the system.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Roles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/Role')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/admin/roles',
        operationId: 'addRole',
        description: 'Create a new role.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Role')
        ),
        tags: ['Administration: Roles'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Role')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/admin/role/{id}',
        operationId: 'getRole',
        description: 'Retrieve details for a single current role.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Roles'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Role')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/admin/role/{id}',
        operationId: 'editRole',
        description: 'Update details of a single role.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/Role')
        ),
        tags: ['Administration: Roles'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/admin/role/{id}',
        operationId: 'deleteRole',
        description: 'Delete a single role.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Roles'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
final class RolesController extends AbstractApiCrudController
{
    use CanSortResults;

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

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('(r.name LIKE :name)')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        $result = parent::viewRecord($record, $request);

        if ($record instanceof Role) {
            $result['is_super_admin'] = $record->getIdRequired() === $this->superAdminRole->getIdRequired();
        }

        return $result;
    }

    protected function editRecord(?array $data, ?object $record = null, array $context = []): object
    {
        if (
            $record instanceof Role
            && $this->superAdminRole->getIdRequired() === $record->getIdRequired()
        ) {
            throw new RuntimeException('Cannot modify the Super Administrator role.');
        }

        return parent::editRecord($data, $record, $context);
    }

    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof Role)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        if ($this->superAdminRole->getIdRequired() === $record->getIdRequired()) {
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
        $existingPerms = $role->getPermissions();
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
