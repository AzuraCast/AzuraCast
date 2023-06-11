<?php

declare(strict_types=1);

namespace App\Controller\Api\Internal;

use App\Container\EntityManagerAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Controller\SingleActionInterface;
use App\Entity\Repository\StorageLocationRepository;
use App\Entity\SftpUser;
use App\Entity\StorageLocation;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media\BatchUtilities;
use App\Message\AddNewMediaMessage;
use League\Flysystem\PathPrefixer;
use LogicException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\Messenger\MessageBus;
use Throwable;

final class SftpEventAction implements SingleActionInterface
{
    use LoggerAwareTrait;
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly MessageBus $messageBus,
        private readonly BatchUtilities $batchUtilities,
        private readonly StorageLocationRepository $storageLocationRepo
    ) {
    }

    public function __invoke(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $errorResponse = $response->withStatus(500)->withJson(['success' => false]);

        $parsedBody = (array)$request->getParsedBody();
        $action = $parsedBody['action'] ?? null;
        $username = $parsedBody['username'] ?? null;
        $path = $parsedBody['path'] ?? null;
        $targetPath = $parsedBody['target_path'] ?? null;
        $sshCmd = $parsedBody['ssh_cmd'] ?? null;

        $this->logger->notice(
            'SFTP file event triggered',
            [
                'action' => $action,
                'username' => $username,
                'path' => $path,
                'targetPath' => $targetPath,
                'sshCmd' => $sshCmd,
            ]
        );

        // Determine which station the username belongs to.
        $sftpUser = $this->em->getRepository(SftpUser::class)->findOneBy(
            [
                'username' => $username,
            ]
        );

        if (!$sftpUser instanceof SftpUser) {
            $this->logger->error('SFTP Username not found.', ['username' => $username]);
            return $errorResponse;
        }

        $storageLocation = $sftpUser->getStation()->getMediaStorageLocation();

        if (!$storageLocation->isLocal()) {
            $this->logger->error(sprintf('Storage location "%s" is not local.', $storageLocation));
            return $errorResponse;
        }

        if (null === $path) {
            $this->logger->error('No path specified for action.');
            return $errorResponse;
        }

        try {
            match ($action) {
                'upload' => $this->handleNewUpload($storageLocation, $path),
                'pre-delete' => $this->handleDelete($storageLocation, $path),
                'rename' => $this->handleRename($storageLocation, $path, $targetPath),
                default => null,
            };

            return $response->withJson(['success' => true]);
        } catch (Throwable $e) {
            $this->logger->error(
                sprintf('SFTP Event: %s', $e->getMessage()),
                [
                    'exception' => $e,
                ]
            );

            return $errorResponse;
        }
    }

    private function handleNewUpload(
        StorageLocation $storageLocation,
        string $path
    ): void {
        $pathPrefixer = new PathPrefixer($storageLocation->getPath(), DIRECTORY_SEPARATOR);
        $relativePath = $pathPrefixer->stripPrefix($path);

        $this->logger->notice(
            'Processing new SFTP upload.',
            [
                'storageLocation' => (string)$storageLocation,
                'path' => $relativePath,
            ]
        );

        $message = new AddNewMediaMessage();
        $message->storage_location_id = $storageLocation->getIdRequired();
        $message->path = $relativePath;

        $this->messageBus->dispatch($message);
    }

    private function handleDelete(
        StorageLocation $storageLocation,
        string $path
    ): void {
        $pathPrefixer = new PathPrefixer($storageLocation->getPath(), DIRECTORY_SEPARATOR);
        $relativePath = $pathPrefixer->stripPrefix($path);

        $this->logger->notice(
            'Processing SFTP file/folder deletion.',
            [
                'storageLocation' => (string)$storageLocation,
                'path' => $relativePath,
            ]
        );

        $directories = [];
        $files = [];

        if (is_dir($path)) {
            $directories[] = $relativePath;
        } else {
            $files[] = $relativePath;
        }

        $fs = $this->storageLocationRepo->getAdapter($storageLocation)->getFilesystem();

        $this->batchUtilities->handleDelete(
            $files,
            $directories,
            $storageLocation,
            $fs
        );

        $fs->delete($relativePath);
    }

    private function handleRename(
        StorageLocation $storageLocation,
        string $path,
        ?string $newPath
    ): void {
        if (null === $newPath) {
            throw new LogicException('No new path specified for rename.');
        }

        $pathPrefixer = new PathPrefixer($storageLocation->getPath(), DIRECTORY_SEPARATOR);

        $from = $pathPrefixer->stripPrefix($path);
        $to = $pathPrefixer->stripPrefix($newPath);

        $this->logger->notice(
            'Processing SFTP file/folder rename.',
            [
                'storageLocation' => (string)$storageLocation,
                'from' => $from,
                'to' => $to,
            ]
        );

        $this->batchUtilities->handleRename(
            $from,
            $to,
            $storageLocation
        );
    }
}
