<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Message;
use App\MessageQueue;
use App\Radio\Filesystem;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class SftpUploadCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Entity\Repository\StationRepository $stationRepo,
        LoggerInterface $logger,
        Filesystem $filesystem,
        MessageQueue $messageQueue,
        string $action = null,
        string $username = null,
        string $path = null,
        string $targetPath = null,
        string $sshCmd = null
    ) {
        $logger->info('SFTP file uploaded', ['path' => $path]);

        // Determine which station the username belongs to.
        $userRepo = $em->getRepository(Entity\SftpUser::class);

        $sftpUser = $userRepo->findOneBy([
            'username' => $username,
        ]);

        if (!$sftpUser instanceof Entity\SftpUser) {
            $logger->error('SFTP Username not found.', ['username' => $username]);
            return;
        }

        $station = $sftpUser->getStation();

        $fs = $filesystem->getForStation($station);
        $fs->flushAllCaches();

        $relative_path = str_replace($station->getRadioMediaDir() . '/', '', $path);

        $message = new Message\AddNewMediaMessage;
        $message->station_id = $station->getId();
        $message->path = $relative_path;
        $messageQueue->produce($message);
    }
}
