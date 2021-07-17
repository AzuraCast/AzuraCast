<?php

declare(strict_types=1);

namespace App\Console\Command\Internal;

use App\Console\Command\CommandAbstract;
use App\Entity\SftpUser;
use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use const JSON_NUMERIC_CHECK;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;

class SftpAuthCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em
    ): int {
        $username = getenv('SFTPGO_AUTHD_USERNAME') ?: null;
        $password = getenv('SFTPGO_AUTHD_PASSWORD') ?: null;
        $pubKey = getenv('SFTPGO_AUTHD_PUBLIC_KEY') ?: null;

        $sftpUser = $em->getRepository(SftpUser::class)->findOneBy(['username' => $username]);

        if ($sftpUser instanceof SftpUser && $sftpUser->authenticate($password, $pubKey)) {
            $storageLocation = $sftpUser->getStation()->getMediaStorageLocation();

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

        $io->write(json_encode(['username' => ''], JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES));
        return 1;
    }
}
