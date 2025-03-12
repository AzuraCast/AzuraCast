<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Rsas;

use App\Controller\SingleActionInterface;
use App\Entity\Api\Status;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Radio\Frontend\Rsas;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[OA\Delete(
    path: '/admin/rsas/license',
    operationId: 'deleteRsasLicense',
    summary: 'Removes the Rocket Streaming Audio Server (RSAS) license.',
    tags: [OpenApi::TAG_ADMIN],
    responses: [
        new OpenApi\Response\Success(),
        new OpenApi\Response\AccessDenied(),
        new OpenApi\Response\GenericError(),
    ]
)]
final class DeleteLicenseAction implements SingleActionInterface
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $licensePath = Rsas::getLicensePath();
        @unlink($licensePath);

        return $response->withJson(Status::success());
    }
}
