<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Rsas;
use App\Service\Flow;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Post(
    path: '/admin/rsas/license',
    operationId: 'postRsasLicense',
    summary: 'Upload a new Rocket Streaming Audio Server (RSAS) license key.',
    requestBody: new OA\RequestBody(ref: OpenApi::REF_REQUEST_BODY_FLOW_FILE_UPLOAD),
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class PostLicenseAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $flowResponse = Flow::process($request, $response);
        if ($flowResponse instanceof ResponseInterface) {
            return $flowResponse;
        }

        $licensePath = Rsas::getLicensePath();

        @unlink($licensePath);
        $flowResponse->moveTo($licensePath);

        return $response->withJson(Status::success());
    }
}
