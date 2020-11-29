<?php

namespace App\Controller\Api\Admin;

use App\Acl;
use App\Http\Response;
use App\Http\ServerRequest;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;

class PermissionsController
{
    protected Acl $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    /**
     * @OA\Get(path="/admin/permissions",
     *   tags={"Administration: Roles"},
     *   description="Return a list of all available permissions.",
     *   @OA\Response(
     *     response=200,
     *     description="Success",
     *   ),
     *   @OA\Response(response=403, description="Access denied"),
     *   security={{"api_key": {}}},
     * )
     *
     * @param ServerRequest $request
     * @param Response $response
     */
    public function __invoke(ServerRequest $request, Response $response): ResponseInterface
    {
        $permissions = [];
        foreach ($this->acl->listPermissions() as $group => $actions) {
            foreach ($actions as $action_id => $action_name) {
                $permissions[$group][] = [
                    'id' => $action_id,
                    'name' => $action_name,
                ];
            }
        }

        return $response->withJson($permissions);
    }
}
