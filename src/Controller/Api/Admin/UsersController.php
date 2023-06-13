<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Controller\Api\Traits\CanSortResults;
use App\Controller\Frontend\Account\MasqueradeAction;
use App\Entity\Api\Error;
use App\Entity\Api\Status;
use App\Entity\User;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

/** @extends AbstractApiCrudController<User> */
#[
    OA\Get(
        path: '/admin/users',
        operationId: 'getUsers',
        description: 'List all current users in the system.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(ref: '#/components/schemas/User')
                )
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Post(
        path: '/admin/users',
        operationId: 'addUser',
        description: 'Create a new user.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/User')
        ),
        tags: ['Administration: Users'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Get(
        path: '/admin/user/{id}',
        operationId: 'getUser',
        description: 'Retrieve details for a single current user.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Users'],
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
            new OA\Response(
                response: 200,
                description: 'Success',
                content: new OA\JsonContent(ref: '#/components/schemas/User')
            ),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Put(
        path: '/admin/user/{id}',
        operationId: 'editUser',
        description: 'Update details of a single user.',
        security: OpenApi::API_KEY_SECURITY,
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: '#/components/schemas/User')
        ),
        tags: ['Administration: Users'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    ),
    OA\Delete(
        path: '/admin/user/{id}',
        operationId: 'deleteUser',
        description: 'Delete a single user.',
        security: OpenApi::API_KEY_SECURITY,
        tags: ['Administration: Users'],
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
            new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
            new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
            new OA\Response(ref: OpenApi::REF_RESPONSE_NOT_FOUND, response: 404),
            new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
        ]
    )
]
class UsersController extends AbstractApiCrudController
{
    use CanSortResults;

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

        $searchPhrase = trim($request->getParam('searchPhrase', ''));
        if (!empty($searchPhrase)) {
            $qb->andWhere('(e.name LIKE :name OR e.email LIKE :name)')
                ->setParameter('name', '%' . $searchPhrase . '%');
        }

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    protected function viewRecord(object $record, ServerRequest $request): mixed
    {
        if (!($record instanceof User)) {
            throw new InvalidArgumentException(sprintf('Record must be an instance of %s.', $this->entityClass));
        }

        $return = $this->toArray($record);

        $isInternal = ('true' === $request->getParam('internal', 'false'));
        $router = $request->getRouter();
        $csrf = $request->getCsrf();
        $currentUser = $request->getUser();

        $return['is_me'] = $currentUser->getIdRequired() === $record->getIdRequired();

        $return['links'] = [
            'self' => $router->fromHere(
                routeName: $this->resourceRouteName,
                routeParams: ['id' => $record->getIdRequired()],
                absolute: !$isInternal
            ),
            'masquerade' => $router->fromHere(
                routeName: 'account:masquerade',
                routeParams: [
                    'id' => $record->getIdRequired(),
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
        if ($record->getId() === $currentUser->getId()) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('You cannot modify yourself.')));
        }

        $this->editRecord((array)$request->getParsedBody(), $record);

        return $response->withJson(Status::updated());
    }

    protected function fromArray(array $data, ?object $record = null, array $context = []): object
    {
        /** @var User $record */
        $record = parent::fromArray($data, $record, $context);

        if (!empty($data['new_password'])) {
            $record->setNewPassword($data['new_password']);
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
        if ($record->getId() === $currentUser->getId()) {
            return $response->withStatus(403)
                ->withJson(new Error(403, __('You cannot remove yourself.')));
        }

        $this->deleteRecord($record);

        return $response->withJson(Status::deleted());
    }
}
