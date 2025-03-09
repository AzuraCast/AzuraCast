<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\BackupMessage;
use App\OpenApi;
use App\Utilities\File;
use OpenApi\Attributes as OA;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

#[
    OA\Post(
        path: '/admin/backups/run',
        operationId: 'postAdminDoBackup',
        summary: 'Initialize a manual backup.',
        tags: [OpenApi::TAG_ADMIN_BACKUPS],
        responses: [
            // TODO API Response Body
            new OpenApi\Response\Success(),
            new OpenApi\Response\AccessDenied(),
            new OpenApi\Response\NotFound(),
            new OpenApi\Response\GenericError(),
        ]
    )
]
final class RunAction implements SingleActionInterface
{
    public function __construct(
        private readonly MessageBus $messageBus,
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $data = (array)$request->getParsedBody();

        $tempFile = File::generateTempPath('backup.log');
        touch($tempFile);

        $storageLocationId = (int)($data['storage_location'] ?? 0);
        if ($storageLocationId <= 0) {
            $storageLocationId = null;
        }

        $message = new BackupMessage();
        $message->storageLocationId = $storageLocationId;
        $message->path = $data['path'] ?? null;
        $message->excludeMedia = $data['exclude_media'] ?? false;
        $message->outputPath = $tempFile;
        $this->messageBus->dispatch($message);

        $router = $request->getRouter();
        return $response->withJson([
            'storageLocationId' => $message->storageLocationId,
            'path' => $message->path,
            'excludeMedia' => $message->excludeMedia,
            'outputPath' => $message->outputPath,
            'links' => [
                'log' => $router->named(
                    'api:admin:backups:log',
                    ['path' => basename($tempFile)]
                ),
            ],
        ]);
    }
}
