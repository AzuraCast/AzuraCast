<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin;

use App\Entity\Api\Admin\SsoTokenDetails;
use App\Entity\Api\Admin\SsoTokenGenerateResponse;
use App\Entity\Api\Admin\SsoTokenList;
use App\Entity\Api\Admin\SsoTokenResponse;
use App\Entity\Repository\UserRepository;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\SsoService;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SsoController
{
    public function __construct(
        private readonly SsoService $ssoService,
        private readonly UserRepository $userRepo,
        private readonly ValidatorInterface $validator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Generate a new SSO token for a user.
     */
    #[OA\Post(
        path: '/admin/sso/generate',
        operationId: 'adminGenerateSsoToken',
        summary: 'Generate a new SSO token for a user.',
        description: 'Creates a one-time, time-limited login token that can be used for SSO integration with external systems like WHMCS.',
        tags: [OpenApi::TAG_ADMIN],
        requestBody: new OA\RequestBody(
            content: new OA\JsonContent(
                required: ['user_id'],
                properties: [
                    new OA\Property(
                        property: 'user_id',
                        type: 'integer',
                        description: 'The ID of the user to generate a token for.',
                        example: 123
                    ),
                    new OA\Property(
                        property: 'comment',
                        type: 'string',
                        description: 'Optional comment describing the token.',
                        example: 'WHMCS SSO Login',
                        maxLength: 255
                    ),
                    new OA\Property(
                        property: 'expires_in',
                        type: 'integer',
                        description: 'How many seconds until the token expires (60-3600).',
                        example: 300,
                        minimum: 60,
                        maximum: 3600
                    ),
                    new OA\Property(
                        property: 'ip_address',
                        type: 'string',
                        description: 'IP address that will be using the token (for audit purposes).',
                        example: '192.168.1.100',
                        format: 'ipv4'
                    )
                ]
            )
        ),
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(ref: '#/components/schemas/Api_Admin_SsoTokenGenerateResponse')
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )]
    public function generateToken(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $data = $request->getParsedBody();

            // Validate input
            $constraints = $this->getValidationConstraints();
            $violations = $this->validator->validate($data, $constraints);

            if (count($violations) > 0) {
                $errors = [];
                foreach ($violations as $violation) {
                    $errors[$violation->getPropertyPath()] = $violation->getMessage();
                }
                return $response->withStatus(422)->withJson([
                    'success' => false,
                    'error' => 'Validation failed',
                    'details' => $errors,
                ]);
            }

            $userId = (int) $data['user_id'];
            $comment = $data['comment'] ?? 'SSO Login';
            $expiresIn = (int) ($data['expires_in'] ?? 300); // 5 minutes default
            $ipAddress = $data['ip_address'] ?? null;

            // Validate user exists
            $user = $this->userRepo->find($userId);
            if (!$user) {
                return $response->withStatus(404)->withJson([
                    'success' => false,
                    'error' => 'User not found',
                ]);
            }

            // Generate token
            $token = $this->ssoService->generateToken(
                userId: $userId,
                comment: $comment,
                expiresIn: $expiresIn,
                ipAddress: $ipAddress
            );

            if (!$token) {
                return $response->withStatus(500)->withJson([
                    'success' => false,
                    'error' => 'Failed to generate SSO token',
                ]);
            }

            // Generate full SSO URL
            $baseUrl = $request->getScheme() . '://' . $request->getHeaderLine('Host');
            $ssoUrl = $this->ssoService->generateSsoUrl(
                userId: $userId,
                baseUrl: $baseUrl,
                comment: $comment,
                expiresIn: $expiresIn,
                ipAddress: $ipAddress
            );

            $result = [
                'token_id' => $token->id,
                'expires_at' => $token->expires_at,
                'expires_in' => $token->expires_at - time(),
                'sso_url' => $ssoUrl,
                'user' => [
                    'id' => $user->id,
                    'email' => $user->email,
                    'name' => $user->name,
                ],
            ];

            return $response->withStatus(201)->withJson([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('SSO token generation failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $response->withStatus(500)->withJson([
                'success' => false,
                'error' => 'Failed to generate SSO token',
            ]);
        }
    }

    /**
     * List active SSO tokens for a user.
     */
    #[OA\Get(
        path: '/admin/sso/user/{user_id}/tokens',
        operationId: 'adminListUserSsoTokens',
        summary: 'List active SSO tokens for a user.',
        description: 'Retrieves all active (non-expired, non-used) SSO tokens for a specific user.',
        tags: [OpenApi::TAG_ADMIN],
        parameters: [
            new OA\Parameter(
                name: 'user_id',
                description: 'The ID of the user to list tokens for.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 123)
            )
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'array',
                            items: new OA\Items(ref: '#/components/schemas/Api_Admin_SsoTokenList')
                        )
                    ]
                )
            ),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )]
    public function listTokens(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $userId = (int) $request->getAttribute('user_id');

            // Validate user exists
            $user = $this->userRepo->find($userId);
            if (!$user) {
                return $response->withStatus(404)->withJson([
                    'success' => false,
                    'error' => 'User not found',
                ]);
            }

            $tokens = $this->ssoService->getUserTokens($userId);

            $result = array_map(function ($token) {
                return [
                    'id' => $token->id,
                    'comment' => $token->comment,
                    'created_at' => $token->created_at,
                    'expires_at' => $token->expires_at,
                    'expires_in' => $token->expires_at - time(),
                    'is_valid' => $token->isValid(),
                    'ip_address' => $token->ip_address,
                ];
            }, $tokens);

            return $response->withJson([
                'success' => true,
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to list SSO tokens', [
                'user_id' => $request->getAttribute('user_id'),
                'error' => $e->getMessage(),
            ]);

            return $response->withStatus(500)->withJson([
                'success' => false,
                'error' => 'Failed to list SSO tokens',
            ]);
        }
    }

    /**
     * Revoke all SSO tokens for a user.
     */
    #[OA\Delete(
        path: '/admin/sso/user/{user_id}/tokens',
        operationId: 'adminRevokeUserSsoTokens',
        summary: 'Revoke all SSO tokens for a user.',
        description: 'Immediately revokes all active SSO tokens for a specific user.',
        tags: [OpenApi::TAG_ADMIN],
        parameters: [
            new OA\Parameter(
                name: 'user_id',
                description: 'The ID of the user to revoke tokens for.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer', example: 123)
            )
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'revoked_count', type: 'integer', example: 3),
                                new OA\Property(
                                    property: 'user',
                                    type: 'object',
                                    properties: [
                                        new OA\Property(property: 'id', type: 'integer', example: 123),
                                        new OA\Property(property: 'email', type: 'string', example: 'user@example.com'),
                                        new OA\Property(property: 'name', type: 'string', example: 'User Name')
                                    ]
                                )
                            ]
                        )
                    ]
                )
            ),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )]
    public function revokeTokens(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $userId = (int) $request->getAttribute('user_id');

            // Validate user exists
            $user = $this->userRepo->find($userId);
            if (!$user) {
                return $response->withStatus(404)->withJson([
                    'success' => false,
                    'error' => 'User not found',
                ]);
            }

            $revokedCount = $this->ssoService->revokeUserTokens($userId);

            return $response->withJson([
                'success' => true,
                'data' => [
                    'revoked_count' => $revokedCount,
                    'user' => [
                        'id' => $user->id,
                        'email' => $user->email,
                        'name' => $user->name,
                    ],
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to revoke SSO tokens', [
                'user_id' => $request->getAttribute('user_id'),
                'error' => $e->getMessage(),
            ]);

            return $response->withStatus(500)->withJson([
                'success' => false,
                'error' => 'Failed to revoke SSO tokens',
            ]);
        }
    }

    /**
     * Clean up expired SSO tokens.
     */
    #[OA\Delete(
        path: '/admin/sso/cleanup',
        operationId: 'adminCleanupSsoTokens',
        summary: 'Clean up expired SSO tokens.',
        description: 'Removes all expired SSO tokens from the database.',
        tags: [OpenApi::TAG_ADMIN],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    type: 'object',
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(
                            property: 'data',
                            type: 'object',
                            properties: [
                                new OA\Property(property: 'cleaned_count', type: 'integer', example: 5)
                            ]
                        )
                    ]
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )]
    public function cleanupTokens(ServerRequest $request, Response $response): ResponseInterface
    {
        try {
            $cleanedCount = $this->ssoService->cleanupExpiredTokens();

            return $response->withJson([
                'success' => true,
                'data' => [
                    'cleaned_count' => $cleanedCount,
                ],
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to cleanup SSO tokens', [
                'error' => $e->getMessage(),
            ]);

            return $response->withStatus(500)->withJson([
                'success' => false,
                'error' => 'Failed to cleanup SSO tokens',
            ]);
        }
    }

    private function getValidationConstraints(): array
    {
        return [
            'user_id' => [
                new Assert\NotBlank([
                    'message' => 'User ID is required',
                ]),
                new Assert\Type([
                    'type' => 'integer',
                    'message' => 'User ID must be an integer',
                ]),
                new Assert\GreaterThan([
                    'value' => 0,
                    'message' => 'User ID must be greater than 0',
                ]),
            ],
            'comment' => [
                new Assert\Optional([
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'Comment must be a string',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Comment cannot exceed {{ limit }} characters',
                    ]),
                ]),
            ],
            'expires_in' => [
                new Assert\Optional([
                    new Assert\Type([
                        'type' => 'integer',
                        'message' => 'Expires in must be an integer',
                    ]),
                    new Assert\Range([
                        'min' => 60,
                        'max' => 3600,
                        'minMessage' => 'Expires in must be at least {{ limit }} seconds',
                        'maxMessage' => 'Expires in cannot exceed {{ limit }} seconds',
                    ]),
                ]),
            ],
            'ip_address' => [
                new Assert\Optional([
                    new Assert\Type([
                        'type' => 'string',
                        'message' => 'IP address must be a string',
                    ]),
                    new Assert\Ip([
                        'message' => 'IP address must be a valid IP address',
                    ]),
                ]),
            ],
        ];
    }
}
