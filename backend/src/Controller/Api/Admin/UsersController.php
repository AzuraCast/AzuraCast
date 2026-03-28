<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSearchResults;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\Frontend\Account\MasqueradeAction;
use App\Entity\Api\Admin\UserWithDetails;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\Types;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

/** @extends AbstractApiCrudController<User> */
#[
    OA\Get(
        path: '/admin/users',
        operationId: 'getUsers',
        summary: 'List all current users in the system.',
        tags: [OpenApi::TAG_ADMIN_USERS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: UserWithDetails::class)
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/admin/users',
        operationId: 'addUser',
        summary: 'Create a new user.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: User::class)
        ),
        tags: [OpenApi::TAG_ADMIN_USERS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: UserWithDetails::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/user/{id}',
        operationId: 'getUser',
        summary: 'Retrieve details for a single current user.',
        tags: [OpenApi::TAG_ADMIN_USERS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: UserWithDetails::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Put(
        path: '/admin/user/{id}',
        operationId: 'editUser',
        summary: 'Update details of a single user.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: User::class)
        ),
        tags: [OpenApi::TAG_ADMIN_USERS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/admin/user/{id}',
        operationId: 'deleteUser',
        summary: 'Delete a single user.',
        tags: [OpenApi::TAG_ADMIN_USERS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'User ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', format: 'int64')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
class UsersController extends AbstractApiCrudController
{
    use CanSortResults;
    use CanSearchResults;

    protected string $entityClass = User::class;
    protected string $resourceRouteName = 'api:admin:user';

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(User::class, 'e');

        $qb = $this->sortQueryBuilder(
            $request,
            $qb,
            [
                'name' => 'e.name',
            ],
            'e.name'
        );

        $qb = $this->searchQueryBuilder(
            $request,
            $qb,
            [
                'e.name',
                'e.email',
            ]
        );

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        $return = $this->toArray($record);

        $isInternal = $request->isInternal();
        $router = $request->getRouter();
        $csrf = $request->getCsrf();
        $currentUser = $request->getUser();

        $return['is_me'] = $currentUser->id === $record->id;

        $return['links'] = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->id],
                absolute: !$isInternal
            ),
            'masquerade' => $router->fromHere(
                routeName: 'account:masquerade',
                routeParams: [
                    'id' => $record->id,
                    'csrf' => $csrf->generate(MasqueradeAction::CSRF_NAMESPACE),
                ],
                absolute: !$isInternal
            ),
        ];

        return $return;
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $record = $this->getRecord($request, $params);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $currentUser = $request->getUser();
        if ($record->id === $currentUser->id) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('You cannot modify yourself.')));
        }

        $this->editRecord((array)$request->getParsedBody(), $record);

        return $response->withJson(Status::updated());
    }

    protected function fromArray(array $data, ?object $record = null, array $context = []): object
    {
        $record = parent::fromArray($data, $record, $context);

        if (isset($data['new_password'])) {
            $record->setNewPassword(Types::stringOrNull($data['new_password'], true));
        }

        return $record;
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $record = $this->getRecord($request, $params);

        if (null === $record) {
            return $response->withStatus(404)
                ->withJson(Error::notFound());
        }

        $currentUser = $request->getUser();
        if ($record->id === $currentUser->id) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('You cannot remove yourself.')));
        }

        $this->deleteRecord($record);

        return $response->withJson(Status::deleted());
    }
}
