<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Entity\Api\Status;
use App\Flysystem\ExtendedFilesystemInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\OpenApi;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;

#[
    OA\Delete(
        path: '/admin/backups/delete/{path}',
        operationId: 'deleteBackup',
        summary: 'Delete a given backup.',
        tags: [OpenApi::TAG_ADMIN_BACKUPS],
        parameters: [
            new OA\Parameter(
                name: 'path',
                description: 'Download path (base64-encoded "StorageLocationID | Path")',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'string')
            ),
        ],
        responses: [
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class DeleteAction extends AbstractFileAction
{
    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        /** @var string $path */
        $path = $params['path'];

        [$path, $fs] = $this->getFile($path);

        /** @var ExtendedFilesystemInterface $fs */
        $fs->delete($path);

        return $response->withJson(Status::deleted());
    }
}
