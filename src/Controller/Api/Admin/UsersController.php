<?php
namespace App\Controller\Api\Admin;

use App\Entity;
use App\Controller\Api\AbstractGenericCrudController;

/**
 * @see \App\Provider\ApiProvider
 */
class UsersController extends AbstractGenericCrudController
{
    protected $entityClass = Entity\User::class;
    protected $resourceRouteName = 'api:admin:user';

    /**
     * @OA\Get(path="/admin/users",
     *   tags={"Administration: Users"},
     *   description="List all current users in the system.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": \App\Acl::GLOBAL_USERS}},
     * )
     *
     * @OA\Post(path="/admin/users",
     *   tags={"Administration: Users"},
     *   description="Create a new user.",
     *   @OA\RequestBody(ref="#/components/schemas/User"),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/User")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": \App\Acl::GLOBAL_USERS}},
     * )
     *
     * @OA\Get(path="/admin/user/{id}",
     *   tags={"Administration: Users"},
     *   description="Retrieve details for a single current user.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/User")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": \App\Acl::GLOBAL_USERS}},
     * )
     *
     * @OA\Put(path="/admin/user/{id}",
     *   tags={"Administration: Users"},
     *   description="Update details of a single user.",
     *   @OA\RequestBody(ref="#/components/schemas/User"),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/API_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": \App\Acl::GLOBAL_USERS}},
     * )
     *
     * @OA\Delete(path="/admin/user/{id}",
     *   tags={"Administration: Users"},
     *   description="Delete a single user.",
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/API_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": \App\Acl::GLOBAL_USERS}},
     * )
     */

}
