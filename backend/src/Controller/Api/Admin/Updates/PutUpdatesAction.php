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
    description: 'Attempts to trigger a web-based update.',
    tags: ['Administration: General'],
    responses: [
        new OA\Response(ref: OpenApi::REF_RESPONSE_SUCCESS, response: 200),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class PutUpdatesAction implements SingleActionInterface
{
    public function __construct(
        private readonly WebUpdater $webUpdater
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
