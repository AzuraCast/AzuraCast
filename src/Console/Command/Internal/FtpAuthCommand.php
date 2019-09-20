<?php
namespace App\Console\Command\Internal;

use App\Service\Ftp;
use Azura\Console\Command\CommandAbstract;
use Symfony\Component\Console\Style\SymfonyStyle;

class FtpAuthCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        Ftp $ftp
    ) {
        $username = getenv('AUTHD_ACCOUNT');
        $password = getenv('AUTHD_PASSWORD');

        $ftp_output = $ftp->auth($username, $password);
        foreach ($ftp_output as $output_ln) {
            $io->writeln($output_ln);
        }
        return null;
    }
}
