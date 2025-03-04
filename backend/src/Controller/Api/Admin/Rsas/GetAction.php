<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\RsasStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Rsas;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/admin/rsas',
    operationId: 'getRsas',
    description: 'Get the current Rocket Streaming Audio Server (RSAS) status.',
    tags: ['Administration: General'],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                ref: RsasStatus::class
            )
        ),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class GetAction implements SingleActionInterface
{
    public function __construct(
        private readonly Rsas $rsas,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            new RsasStatus(
                $this->rsas->getVersion(),
                $this->rsas->hasLicense()
            )
        );
    }
}
