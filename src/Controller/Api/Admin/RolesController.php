<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use App\Controller\Api\AbstractGenericCrudController;
use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

/**
 * @see \App\Provider\ApiProvider
 */
class RolesController extends AbstractGenericCrudController
{
    protected $entityClass = Entity\Role::class;
    protected $resourceRouteName = 'api:admin:role';

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

    protected function _denormalizeToRecord($data, $record, array $context = []): void
    {
        /** @var Entity\Repository\RolePermissionRepository $rp_repo */
        $rp_repo = $this->em->getRepository(Entity\RolePermission::class);

        parent::_denormalizeToRecord($data, $record, array_merge($context, [
            AbstractNormalizer::CALLBACKS => [
                'permissions' => function(array $value, $record) use ($rp_repo) {
                    if ($record instanceof Entity\Role) {
                        $rp_repo->setPermissions($record, $value);
                    }
                    return null;
                },
            ],
        ]));
    }
}
