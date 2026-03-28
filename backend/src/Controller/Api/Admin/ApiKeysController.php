<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Account\NewApiKey as AccountNewApiKey;
use App\Entity\Api\Admin\NewApiKey;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\ApiKey;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Security\SplitToken;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * @extends AbstractApiCrudController<ApiKey>
 */
#[
    OA\Get(
        path: '/admin/api-keys',
        operationId: 'adminListApiKeys',
        summary: 'List all current API keys across the system.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: ApiKey::class),
                            new OA\Schema(ref: HasLinks::class),
                        ]
                    )
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Post(
        path: '/admin/api-keys',
        operationId: 'adminCreateApiKey',
        summary: 'Create a new API key for a specified user.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: NewApiKey::class)
        ),
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: AccountNewApiKey::class),
                        new OA\Schema(ref: ApiKey::class),
                        new OA\Schema(ref: HasLinks::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/admin/api-key/{id}',
        operationId: 'adminDeleteApiKey',
        summary: 'Delete a single API key.',
        tags: [OpenApi::TAG_ADMIN],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'API Key ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
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
final class ApiKeysController extends AbstractApiCrudController
{
    protected string $entityClass = ApiKey::class;
    protected string $resourceRouteName = 'api:admin:api-key';

    public function __construct(
        Serializer $serializer,
        ValidatorInterface $validator,
        private readonly UserRepository $userRepo,
    ) {
        parent::__construct($serializer, $validator);
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var NewApiKey $createKey */
        $createKey = $this->serializer->denormalize(
            (array)$request->getParsedBody(),
            NewApiKey::class
        );

        $errors = $this->validator->validate($createKey);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        $user = $this->userRepo->findByIdOrEmail($createKey->user);

        if (!($user instanceof User)) {
            throw new InvalidArgumentException('User not found!');
        }

        $newKey = SplitToken::generate();

        $record = new ApiKey(
            $user,
            $newKey,
            $createKey->comment
        );

        $this->em->persist($record);
        $this->em->flush();

        $return = $this->viewRecord($record, $request);
        $return['key'] = (string)$newKey;

        return $response->withJson($return);
    }
}
