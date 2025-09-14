<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Shoutcast;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\ShoutcastStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Shoutcast;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/admin/shoutcast',
    operationId: 'getShoutcast',
    summary: 'Get details about the Shoutcast installation.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: ShoutcastStatus::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final readonly class GetAction implements SingleActionInterface
{
    public function __construct(
        private Shoutcast $shoutcast,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            new ShoutcastStatus($this->shoutcast->getVersion())
        );
    }
}
