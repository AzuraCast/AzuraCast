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
    summary: 'Get information about the Stereo Tool installation.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(
            content: new OA\JsonContent(
                ref: StereoToolStatus::class
            )
        ),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
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
