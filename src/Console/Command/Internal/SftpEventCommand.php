<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Application;
use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Media\BatchUtilities;
use App\Message;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\PathPrefixer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBus;

class SftpEventCommand extends CommandAbstract
{
    public function __construct(
        Application $application,
        protected MessageBus $messageBus,
        protected LoggerInterface $logger,
        protected BatchUtilities $batchUtilities
    ) {
        parent::__construct($application);
    }

    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        string $action = null,
        string $username = null,
        string $path = null,
        string $targetPath = null,
        string $sshCmd = null
    ): int {
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
        $sftpUser = $em->getRepository(Entity\SftpUser::class)->findOneBy(
            [
                'username' => $username,
            ]
        );

        if (!$sftpUser instanceof Entity\SftpUser) {
            $this->logger->error('SFTP Username not found.', ['username' => $username]);
            return 1;
        }

        $storageLocation = $sftpUser->getStation()->getMediaStorageLocation();

        if (!$storageLocation->isLocal()) {
            $this->logger->error(sprintf('Storage location "%s" is not local.', (string)$storageLocation));
            return 1;
        }

        if (null === $path) {
            $this->logger->error('No path specified for action.');
            return 1;
        }

        return match ($action) {
            'upload' => $this->handleNewUpload($storageLocation, $path),
            'pre-delete' => $this->handleDelete($storageLocation, $path),
            'rename' => $this->handleRename($storageLocation, $path, $targetPath),
            default => 1,
        };
    }

    protected function handleNewUpload(
        Entity\StorageLocation $storageLocation,
        string $path
    ): int {
        $pathPrefixer = new PathPrefixer($storageLocation->getPath(), DIRECTORY_SEPARATOR);
        $relativePath = $pathPrefixer->stripPrefix($path);

        /*
        $sanitizedRelativePath = File::sanitizeFileName($relativePath);

        if ($relativePath !== $sanitizedRelativePath) {
            // Rename file to sanitized version.
            $sanitizedFullPath = $pathPrefixer->prefixPath($sanitizedRelativePath);
            rename($path, $sanitizedFullPath);

            $relativePath = $sanitizedRelativePath;
        }
        */

        $this->logger->notice(
            'Processing new SFTP upload.',
            [
                'storageLocation' => (string)$storageLocation,
                'path' => $relativePath,
            ]
        );

        $message = new Message\AddNewMediaMessage();
        $message->storage_location_id = $storageLocation->getIdRequired();
        $message->path = $relativePath;

        $this->messageBus->dispatch($message);

        return 0;
    }

    protected function handleDelete(
        Entity\StorageLocation $storageLocation,
        string $path
    ): int {
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

        $fs = $storageLocation->getFilesystem();

        $this->batchUtilities->handleDelete(
            $files,
            $directories,
            $storageLocation,
            $fs
        );

        $fs->delete($relativePath);

        return 0;
    }

    protected function handleRename(
        Entity\StorageLocation $storageLocation,
        string $path,
        ?string $newPath
    ): int {
        if (null === $newPath) {
            $this->logger->error('No new path specified for rename.');
            return 1;
        }

        $pathPrefixer = new PathPrefixer($storageLocation->getPath(), DIRECTORY_SEPARATOR);

        $from = $pathPrefixer->stripPrefix($path);
        $to = $pathPrefixer->stripPrefix($newPath);

        /*
        $sanitizedTo = File::sanitizeFileName($to);

        if ($to !== $sanitizedTo) {
            $sanitizedNewPath = $pathPrefixer->prefixPath($sanitizedTo);
            rename($newPath, $sanitizedNewPath);

            $to = $sanitizedTo;
        }
        */

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

        return 0;
    }
}
