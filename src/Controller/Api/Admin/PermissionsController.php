<?php
namespace App\Controller\Api\Admin;

use App\Http\Request;
use App\Http\Response;
use Psr\Http\Message\ResponseInterface;

/**
 * @see \App\Provider\ApiProvider
 */
class PermissionsController
{
    /** @var array */
    protected $actions;

    /**
     * PermissionsController constructor.
     * @param array $actions
     */
    public function __construct(array $actions)
    {
        $this->actions = $actions;
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
     * @param Request $request
     * @param Response $response
     * @return ResponseInterface
     */
    public function __invoke(Request $request, Response $response): ResponseInterface
    {
        $permissions = [];



        return $response->withJson($permissions);
    }
}
