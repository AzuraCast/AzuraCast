<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity;
use App\Http\Response;
use App\Http\ServerRequest;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

/**
 * @extends AbstractAdminApiCrudController<Entity\User>
 */
class UsersController extends AbstractAdminApiCrudController
{
    protected string $entityClass = Entity\User::class;
    protected string $resourceRouteName = 'api:admin:user';

    /**
     * @OA\Get(path="/admin/users",
     *   tags={"Administration: Users"},
     *   description="List all current users in the system.",
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Post(path="/admin/users",
     *   tags={"Administration: Users"},
     *   description="Create a new user.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/User")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/User")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
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
     *   security={{"api_key": {}}},
     * )
     *
     * @OA\Put(path="/admin/user/{id}",
     *   tags={"Administration: Users"},
     *   description="Update details of a single user.",
     *   @OA\RequestBody(
     *     @OA\JsonContent(ref="#/components/schemas/User")
     *   ),
     *   @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="User ID",
     *     required=true,
     *     @OA\Schema(type="integer", format="int64")
     *   ),
     *   @OA\Response(response=200, description="Success",
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     */

    /**
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
     *     @OA\JsonContent(ref="#/components/schemas/Api_Status")
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @inheritdoc
     */
    public function deleteAction(ServerRequest $request, Response $response, mixed $id): ResponseInterface
    {
        $record = $this->getRecord($id);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Entity\Api\Error::notFound());
        }

        $current_user = $request->getUser();

        if ($record->getId() === $current_user->getId()) {
            return $response->withStatus(403)
                ->withJson(new Entity\Api\Error(403, __('You cannot remove yourself.')));
        }

        $this->deleteRecord($record);

        return $response->withJson(new Entity\Api\Status(true, __('Record deleted successfully.')));
    }
}
