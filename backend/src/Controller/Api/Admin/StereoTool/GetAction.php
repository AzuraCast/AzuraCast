<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\StereoTool;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Admin\StereoToolStatus;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\StereoTool;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Get(
    path: '/admin/stereo_tool',
    operationId: 'getStereoTool',
    description: 'Get information about the Stereo Tool installation.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OA\Response(
            response: 200,
            description: 'Success',
            content: new OA\JsonContent(
                ref: StereoToolStatus::class
            )
        ),
        new OA\Response(ref: OpenApi::REF_RESPONSE_ACCESS_DENIED, response: 403),
        new OA\Response(ref: OpenApi::REF_RESPONSE_GENERIC_ERROR, response: 500),
    ]
)]
final class GetAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        return $response->withJson(
            new StereoToolStatus(StereoTool::getVersion())
        );
    }
}
