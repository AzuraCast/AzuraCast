<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Controller\Api\AbstractApiCrudController;
use App\Entity\Api\Admin\NewLoginToken;
use App\Entity\Api\Admin\NewLoginTokenResponse;
use App\Entity\Api\Status;
use App\Entity\Api\Traits\HasLinks;
use App\Entity\Repository\UserLoginTokenRepository;
use App\Entity\Repository\UserRepository;
use App\Entity\User;
use App\Entity\UserLoginToken;
use App\Exception\ValidationException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use InvalidArgumentException;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/** @extends AbstractApiCrudController<UserLoginToken> */
#[
    OA\Get(
        path: '/admin/login_tokens',
        operationId: 'getLoginTokens',
        summary: 'List all current unexpired login tokens in the system.',
        tags: [OpenApi::TAG_ADMIN_USERS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'array',
                    items: new OA\Items(
                        allOf: [
                            new OA\Schema(ref: UserLoginToken::class),
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
        path: '/admin/login_tokens',
        operationId: 'addLoginToken',
        summary: 'Create a new login token.',
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(ref: NewLoginToken::class)
        ),
        tags: [OpenApi::TAG_ADMIN_USERS],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: NewLoginTokenResponse::class)
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Get(
        path: '/admin/login_token/{id}',
        operationId: 'getLoginToken',
        summary: 'Retrieve details for a single login token.',
        tags: [OpenApi::TAG_ADMIN_USERS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Token identifier',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    allOf: [
                        new OA\Schema(ref: UserLoginToken::class),
                        new OA\Schema(ref: HasLinks::class),
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    ),
    OA\Delete(
        path: '/admin/login_token/{id}',
        operationId: 'deleteLoginToken',
        summary: 'Delete a single login token.',
        tags: [OpenApi::TAG_ADMIN_USERS],
        parameters: [
            new OA\Parameter(
                name: 'id',
                description: 'Token identifier',
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
class LoginTokensController extends AbstractApiCrudController
{
    protected string $entityClass = UserLoginToken::class;
    protected string $resourceRouteName = 'api:admin:login_token';

    public function __construct(
        Serializer $serializer,
        ValidatorInterface $validator,
        private readonly UserRepository $userRepo,
        private readonly UserLoginTokenRepository $loginTokenRepo,
    ) {
        parent::__construct($serializer, $validator);
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $qb = $this->em->createQueryBuilder()
            ->select('e')
            ->from(UserLoginToken::class, 'e');

        return $this->listPaginatedFromQuery($request, $response, $qb->getQuery());
    }

    public function editAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        throw new RuntimeException('Login tokens cannot be edited.');
    }

    public function createAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        /** @var NewLoginToken $createToken */
        $createToken = $this->serializer->denormalize(
            (array)$request->getParsedBody(),
            NewLoginToken::class
        );

        $errors = $this->validator->validate($createToken);
        if (count($errors) > 0) {
            throw ValidationException::fromValidationErrors($errors);
        }

        $user = $this->userRepo->findByIdOrEmail(
            $createToken->user
        );

        if (!($user instanceof User)) {
            throw new InvalidArgumentException('User not found!');
        }

        [$splitToken, $record] = $this->loginTokenRepo->createToken(
            $user,
            $createToken->type,
            $createToken->comment,
            $createToken->expires_minutes
        );

        $success = Status::created();

        $isInternal = $request->isInternal();
        $router = $request->getRouter();

        $return = new NewLoginTokenResponse(
            $success->success,
            $success->message,
            $success->formatted_message,
            $record,
            [
                'self' => $router->fromHere(
                    routeName: $this->resourceRouteName,
                    routeParams: ['id' => $record->id],
                    absolute: !$isInternal
                ),
                'login' => $router->named(
                    routeName: 'account:login-token',
                    routeParams: ['token' => $splitToken],
                    absolute: true
                ),
            ]
        );

        return $response->withJson(
            (array)$this->serializer->normalize(
                $return,
                null,
                [
                    AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
                    AbstractObjectNormalizer::MAX_DEPTH_HANDLER => fn($innerObject) => $this->displayShortenedObject(
                        $innerObject
                    ),
                    AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => fn($object) => $this->displayShortenedObject(
                        $object
                    ),
                ]
            )
        );
    }
}
