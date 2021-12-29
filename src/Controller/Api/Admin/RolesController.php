<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Controller\Api\Traits\CanSortResults;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractAdminApiCrudController<Entity\Role> */
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
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
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
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
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
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Status')
            ),
            new OA\Response(
                response: 403,
                description: 'Access denied'
            ),
        ]
    )
]
class RolesController extends AbstractAdminApiCrudController
{
    use CanSortResults;

    protected string $entityClass = Entity\Role::class;
    protected string $resourceRouteName = 'api:admin:role';

    public function __construct(
        protected Acl $acl,
        protected Entity\Repository\RolePermissionRepository $permissionRepo,
        ReloadableEntityManagerInterface $em,
        Serializer $serializer,
        ValidatorInterface $validator
    ) {
        parent::__construct($em, $serializer, $validator);
    }

    /**
     * @param ServerRequest $request
     * @param Response $response
     */
    public function listAction(ServerRequest $request, Response $response): ResponseInterface
    {
        $qb = $this->em->createQueryBuilder()
            ->select('r, rp')
            ->from(Entity\Role::class, 'r')
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

    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof Entity\Role)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $superAdminRole = $this->permissionRepo->ensureSuperAdministratorRole();
        if ($superAdminRole->getIdRequired() === $record->getIdRequired()) {
            throw new RuntimeException('Cannot remove the Super Administrator role.');
        }

        parent::deleteRecord($record);
    }

    protected function fromArray(array $data, $record = null, array $context = []): object
    {
        return parent::fromArray(
            $data,
            $record,
            array_merge(
                $context,
                [
                    AbstractNormalizer::CALLBACKS => [
                        'permissions' => function (array $value, Entity\Role $record) {
                            $this->doUpdatePermissions($record, $value);
                            return null;
                        },
                    ],
                ]
            )
        );
    }

    protected function doUpdatePermissions(Entity\Role $role, array $newPermissions): void
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
            foreach ($newPermissions['global'] as $perm_name) {
                if ($this->acl->isValidPermission($perm_name, true)) {
                    $perm_record = new Entity\RolePermission($role, null, $perm_name);
                    $this->em->persist($perm_record);
                }
            }
        }

        if (isset($newPermissions['station'])) {
            foreach ($newPermissions['station'] as $station_id => $station_perms) {
                $station = $this->em->find(Entity\Station::class, $station_id);

                if ($station instanceof Entity\Station) {
                    foreach ($station_perms as $perm_name) {
                        if ($this->acl->isValidPermission($perm_name, false)) {
                            $perm_record = new Entity\RolePermission($role, $station, $perm_name);
                            $this->em->persist($perm_record);
                        }
                    }
                }
            }
        }
    }
}
