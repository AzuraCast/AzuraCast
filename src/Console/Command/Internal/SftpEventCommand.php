<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Media\BatchUtilities;
use App\Message;
use Doctrine\ORM\EntityManagerInterface;
use League\Flysystem\PathPrefixer;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Messenger\MessageBus;

#[AsCommand(
    name: 'azuracast:internal:sftp-event',
    description: 'Send upcoming song feedback from the AutoDJ back to AzuraCast.',
)]
class SftpEventCommand extends CommandAbstract
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected MessageBus $messageBus,
        protected LoggerInterface $logger,
        protected BatchUtilities $batchUtilities,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $action = getenv('SFTPGO_ACTION') ?: null;
        $username = getenv('SFTPGO_ACTION_USERNAME') ?: null;
        $path = getenv('SFTPGO_ACTION_PATH') ?: null;
        $targetPath = getenv('SFTPGO_ACTION_TARGET') ?: null;
        $sshCmd = getenv('SFTPGO_ACTION_SSH_CMD') ?: null;

        $this->logger->notice(
            'SFTP file event triggered',
            [
                'action'     => $action,
                'username'   => $username,
                'path'       => $path,
                'targetPath' => $targetPath,
                'sshCmd'     => $sshCmd,
            ]
        );

        // Determine which station the username belongs to.
        $sftpUser = $this->em->getRepository(Entity\SftpUser::class)->findOneBy(
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

        $this->logger->notice(
            'Processing new SFTP upload.',
            [
                'storageLocation' => (string)$storageLocation,
                'path'            => $relativePath,
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
                'path'            => $relativePath,
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

        $this->logger->notice(
            'Processing SFTP file/folder rename.',
            [
                'storageLocation' => (string)$storageLocation,
                'from'            => $from,
                'to'              => $to,
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
