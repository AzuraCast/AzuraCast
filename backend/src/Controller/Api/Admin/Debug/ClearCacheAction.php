<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Debug;

use App\Console\Application;
use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Put(
        path: '/admin/debug/clear-cache',
        operationId: 'adminDebugClearCache',
        summary: 'Clear the application cache (Redis).',
        tags: [OpenApi::TAG_ADMIN_DEBUG],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final readonly class ClearCacheAction implements SingleActionInterface
{
    public function __construct(
        private Application $console,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $this->console->runCommandWithArgs(
            'cache:clear'
        );

        return $response->withJson(Status::updated());
    }
}
