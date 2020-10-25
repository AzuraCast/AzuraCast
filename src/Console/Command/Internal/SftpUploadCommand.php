<?php

namespace App\Console\Command\Internal;

use App\Console\Application;
use App\Console\Command\CommandAbstract;
use App\Entity;
use App\Flysystem\Filesystem;
use App\Message;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBus;

class SftpUploadCommand extends CommandAbstract
{
    protected MessageBus $messageBus;

    protected LoggerInterface $logger;

    protected Filesystem $filesystem;

    public function __construct(
        Application $application,
        MessageBus $messageBus,
        LoggerInterface $logger,
        Filesystem $filesystem
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
        return $this->handleNewUpload($station, $path);
    }

    protected function handleNewUpload(Entity\Station $station, $path): int
    {
        $fs = $this->filesystem->getForStation($station);
        $fs->flushAllCaches();

        $relativePath = str_replace($station->getRadioMediaDir() . '/', '', $path);

        $this->logger->notice('Processing new SFTP upload for station.', [
            'station' => $station->getName(),
            'path' => $relativePath,
        ]);

        $message = new Message\AddNewMediaMessage();
        $message->station_id = $station->getId();
        $message->path = $relativePath;

        $this->messageBus->dispatch($message);

        return 0;
    }
}
