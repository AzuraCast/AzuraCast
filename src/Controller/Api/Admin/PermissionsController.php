<?php
namespace App\Controller\Api\Admin;

use App\Acl;
use App\Http\ResponseHelper;
use OpenApi\Annotations as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class PermissionsController
{
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
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return ResponseInterface
     */
    public function __invoke(ServerRequestInterface $request, ResponseInterface $response): ResponseInterface
    {
        $permissions = [];
        foreach(Acl::listPermissions() as $group => $actions) {
            foreach($actions as $action_id => $action_name) {
                $permissions[$group][] = [
                    'id' => $action_id,
                    'name' => $action_name,
                ];
            }
        }

        return ResponseHelper::withJson($response, $permissions);
    }
}
