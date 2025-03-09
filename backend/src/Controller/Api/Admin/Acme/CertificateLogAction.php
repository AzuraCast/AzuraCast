<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Acme;

use App\Controller\Api\Traits\HasLogViewer;
use App\Controller\SingleActionInterface;
use App\Entity\Api\LogContents;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Get(
        path: '/admin/acme-log/{path}',
        operationId: 'adminAcmeViewLog',
        summary: 'View the logs of a manually run ACME certificate renewal.',
        tags: [OpenApi::TAG_ADMIN],
        parameters: [
            new OA\Parameter(
                name: 'path',
                description: 'Log path as returned by the Run action.',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(
                content: new OA\JsonContent(
                    ref: LogContents::class
                )
            ),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    ),
]
final class CertificateLogAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $path */
        $path = $params['path'];

        $tempPath = File::validateTempPath($path);

        return $this->streamLogToResponse(
            $request,
            $response,
            $tempPath
        );
    }
}
