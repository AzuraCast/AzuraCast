<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractAdminApiCrudController<Entity\Role>
 */
class RolesController extends AbstractAdminApiCrudController
{
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
     * @OA\Get(path="/admin/roles",
     *   tags={"Administration: Roles"},
     *   description="List all current roles in the system.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/Role"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/admin/roles",
     *   tags={"Administration: Roles"},
     *   description="Create a new role.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Role")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Role")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Get(path="/admin/role/{id}",
     *   tags={"Administration: Roles"},
     *   description="Retrieve details for a single current role.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Role ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Role")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/admin/role/{id}",
     *   tags={"Administration: Roles"},
     *   description="Update details of a single role.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/Role")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Role ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Delete(path="/admin/role/{id}",
     *   tags={"Administration: Roles"},
     *   description="Delete a single role.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Role ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @inheritdoc
     */

    protected function deleteRecord(object $record): void
    {
        if (!($record instanceof Entity\Role)) {
            throw new \InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $superAdminRole = $this->permissionRepo->ensureSuperAdministratorRole();
        if ($superAdminRole->getIdRequired() === $record->getIdRequired()) {
            throw new \RuntimeException('Cannot remove the Super Administrator role.');
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
