<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity\SftpUser;
use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use const JSON_THROW_ON_ERROR;

#[AsCommand(
    name: 'azuracast:internal:sftp-auth',
    description: 'Attempt SFTP authentication.',
)]
class SftpAuthCommand extends CommandAbstract
{
    public function __construct(
        protected EntityManagerInterface $em,
        protected LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $errorResponse = json_encode(['username' => ''], JSON_THROW_ON_ERROR);

        $username = getenv('SFTPGO_AUTHD_USERNAME') ?: '';
        $password = getenv('SFTPGO_AUTHD_PASSWORD') ?: '';
        $pubKey = getenv('SFTPGO_AUTHD_PUBLIC_KEY') ?: '';

        if (empty($username)) {
            $io->write($errorResponse);
            return 1;
        }

        $sftpUser = $this->em->getRepository(SftpUser::class)->findOneBy(['username' => $username]);
        if (!($sftpUser instanceof SftpUser)) {
            $this->logger->notice(
                sprintf(
                    'SFTP user "%s" not found.',
                    $username
                )
            );

            $io->write($errorResponse);
            return 1;
        }

        if (!$sftpUser->authenticate($password, $pubKey)) {
            $this->logger->notice(
                sprintf(
                    'SFTP user "%s" could not authenticate.',
                    $username
                ),
                [
                    'hasPassword' => !empty($password),
                    'hasPubKey' => !empty($pubKey),
                ]
            );

            $io->write($errorResponse);
            return 1;
        }

        $storageLocation = $sftpUser->getStation()->getMediaStorageLocation();

        if (!$storageLocation->isLocal()) {
            $this->logger->error(
                sprintf(
                    'SFTP login failed for user "%s": Storage Location %s is not local.',
                    $username,
                    $storageLocation
                )
            );

            $io->write($errorResponse);
            return 1;
        }

        $quotaRaw = $storageLocation->getStorageQuotaBytes();
        $quota = ($quotaRaw instanceof BigInteger)
            ? (string)$quotaRaw
            : 0;

        $row = [
            'status' => 1,
            'username' => $sftpUser->getUsername(),
            'expiration_date' => 0,
            'home_dir' => $storageLocation->getPath(),
            'uid' => 0,
            'gid' => 0,
            'quota_size' => $quota,
            'permissions' => [
                '/' => ['*'],
            ],
        ];

        $io->write(json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        return 0;
    }
}
