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
    description: 'Get details about the Shoutcast installation.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                ref: ShoutcastStatus::class
            )
        ),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class GetAction implements SingleActionInterface
{
    public function __construct(
        private readonly Shoutcast $shoutcast,
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
