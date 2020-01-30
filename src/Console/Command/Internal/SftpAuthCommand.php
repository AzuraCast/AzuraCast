<?php
namespace App\Console\Command\Internal;

use App\Entity\SftpUser;
use Azura\Console\Command\CommandAbstract;
use Brick\Math\BigInteger;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class SftpAuthCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em
    ) {
        $username = getenv('SFTPGO_AUTHD_USERNAME');
        $password = getenv('SFTPGO_AUTHD_PASSWORD');
        $pubKey = getenv('SFTPGO_AUTHD_PUBLIC_KEY');

        $sftpRepo = $em->getRepository(SftpUser::class);
        $sftpUser = $sftpRepo->findOneBy(['username' => $username]);

        if ($sftpUser instanceof SftpUser && $sftpUser->authenticate($password, $pubKey)) {
            $station = $sftpUser->getStation();

            $quotaRaw = $station->getStorageQuotaBytes();
            $quota = ($quotaRaw instanceof BigInteger)
                ? (string)$quotaRaw
                : 0;

            $row = [
                'status' => 1,
                'username' => $sftpUser->getUsername(),
                'expiration_date' => 0,
                'home_dir' => $station->getRadioMediaDir(),
                'uid' => 0,
                'gid' => 0,
                'quota_size' => $quota,
                'permissions' => [
                    '/' => ['*'],
                ],
            ];

            $io->write(json_encode($row, \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES | \JSON_NUMERIC_CHECK));
            return 0;
        }

        $io->write(json_encode(['username' => ''], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES));
        return 1;
    }
}
