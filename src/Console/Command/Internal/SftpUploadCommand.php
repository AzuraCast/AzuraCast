<?php

namespace App\Console\Command\Internal;

use App\Console\Application;
use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Flysystem\FilesystemManager;
use App\Message;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBus;

class SftpUploadCommand extends CommandAbstract
{
    protected MessageBus $messageBus;

    protected LoggerInterface $logger;

    protected FilesystemManager $filesystem;

    public function __construct(
        Application $application,
        MessageBus $messageBus,
        LoggerInterface $logger,
        FilesystemManager $filesystem
    ) {
        parent::__construct($application);

        $this->messageBus = $messageBus;
        $this->logger = $logger;
        $this->filesystem = $filesystem;
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
        $this->logger->notice('SFTP file uploaded', [
            'action' => $action,
            'username' => $username,
            'path' => $path,
            'targetPath' => $targetPath,
            'sshCmd' => $sshCmd,
        ]);

        // Determine which station the username belongs to.
        $userRepo = $em->getRepository(Entity\SftpUser::class);

        $sftpUser = $userRepo->findOneBy([
            'username' => $username,
        ]);

        if (!$sftpUser instanceof Entity\SftpUser) {
            $this->logger->error('SFTP Username not found.', ['username' => $username]);
            return 1;
        }

        $station = $sftpUser->getStation();
        $storageLocation = $station->getMediaStorageLocation();

        if (!$storageLocation->isLocal()) {
            $this->logger->error(sprintf('Storage location "%s" is not local.', (string)$storageLocation));
            return 1;
        }

        $this->flushCache($storageLocation);

        return $this->handleNewUpload($storageLocation, $path);
    }

    protected function flushCache(Entity\StorageLocation $storageLocation): void
    {
        $adapter = $storageLocation->getStorageAdapter();
        $fs = $this->filesystem->getFilesystemForAdapter($adapter);
        $fs->clearCache(false);
    }

    protected function handleNewUpload(Entity\StorageLocation $storageLocation, $path): int
    {
        $relativePath = str_replace($storageLocation->getPath() . '/', '', $path);

        $this->logger->notice('Processing new SFTP upload.', [
            'storageLocation' => (string)$storageLocation,
            'path' => $relativePath,
        ]);

        $message = new Message\AddNewMediaMessage();
        $message->storage_location_id = $storageLocation->getId();
        $message->path = $relativePath;

        $this->messageBus->dispatch($message);

        return 0;
    }
}
