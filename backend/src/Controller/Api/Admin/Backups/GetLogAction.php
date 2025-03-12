<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

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
        path: '/admin/backups/log/{path}',
        operationId: 'adminBackupsViewLog',
        summary: 'View a specific backup log contents.',
        tags: [OpenApi::TAG_ADMIN_BACKUPS],
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
final class GetLogAction implements SingleActionInterface
{
    use HasLogViewer;

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $path */
        $path = $params['path'];

        $logPath = File::validateTempPath($path);

        return $this->streamLogToResponse($request, $response, $logPath);
    }
}
