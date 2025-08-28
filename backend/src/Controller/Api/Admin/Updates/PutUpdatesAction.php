<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Updates;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Service\WebUpdater;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Put(
    path: '/admin/updates',
    operationId: 'putWebUpdate',
    summary: 'Attempts to trigger a web-based update.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class PutUpdatesAction implements SingleActionInterface
{
    public function __construct(
        private WebUpdater $webUpdater
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $this->webUpdater->triggerUpdate();

        return $response->withJson(Status::success());
    }
}
