<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Entity;
use Doctrine\ORM\EntityManagerInterface;
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
        EntityManagerInterface $em,
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
        $perms = $role->getPermissions();

        if ($perms->count() > 0) {
            foreach ($perms as $existing_perm) {
                $this->em->remove($existing_perm);
            }
            $perms->clear();
        }

        if (!empty($newPermissions['global'])) {
            foreach ($newPermissions['global'] as $perm_name) {
                if ($this->acl->isValidPermission($perm_name, true)) {
                    $perm_record = new Entity\RolePermission($role, null, $perm_name);
                    $this->em->persist($perm_record);
                    $perms->add($perm_record);
                }
            }
        }

        if (!empty($newPermissions['station'])) {
            foreach ($newPermissions['station'] as $station_id => $station_perms) {
                $station = $this->em->find(Entity\Station::class, $station_id);

                if ($station instanceof Entity\Station) {
                    foreach ($station_perms as $perm_name) {
                        if ($this->acl->isValidPermission($perm_name, false)) {
                            $perm_record = new Entity\RolePermission($role, $station, $perm_name);
                            $this->em->persist($perm_record);
                            $perms->add($perm_record);
                        }
                    }
                }
            }
        }
    }
}
