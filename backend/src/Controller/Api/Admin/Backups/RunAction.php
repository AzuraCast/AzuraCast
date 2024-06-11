<?php

declare(strict_types=1);

namespace App\Controller\Api\Admin\Backups;

use App\Controller\SingleActionInterface;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Message\BackupMessage;
use App\Utilities\File;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;

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
