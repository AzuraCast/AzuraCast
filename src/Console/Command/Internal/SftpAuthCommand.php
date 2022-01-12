<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity\SftpUser;
use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use const JSON_NUMERIC_CHECK;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

#[AsCommand(
    name: 'azuracast:internal:sftp-auth',
    description: 'Attempt SFTP authentication.',
)]
class SftpAuthCommand extends CommandAbstract
{
    public function __construct(
        protected EntityManagerInterface $em,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $username = getenv('SFTPGO_AUTHD_USERNAME') ?: null;
        $password = getenv('SFTPGO_AUTHD_PASSWORD') ?: null;
        $pubKey = getenv('SFTPGO_AUTHD_PUBLIC_KEY') ?: null;

        $sftpUser = $this->em->getRepository(SftpUser::class)->findOneBy(['username' => $username]);

        if ($sftpUser instanceof SftpUser && $sftpUser->authenticate($password, $pubKey)) {
            $storageLocation = $sftpUser->getStation()->getMediaStorageLocation();

            $quotaRaw = $storageLocation->getStorageQuotaBytes();
            $quota = ($quotaRaw instanceof BigInteger)
                ? (string)$quotaRaw
                : 0;

            $row = [
                'status'          => 1,
                'username'        => $sftpUser->getUsername(),
                'expiration_date' => 0,
                'home_dir'        => $storageLocation->getPath(),
                'uid'             => 0,
                'gid'             => 0,
                'quota_size'      => $quota,
                'permissions'     => [
                    '/' => ['*'],
                ],
            ];

            $io->write(json_encode($row, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
            return 0;
        }

        $io->write(json_encode(['username' => ''], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        return 1;
    }
}
