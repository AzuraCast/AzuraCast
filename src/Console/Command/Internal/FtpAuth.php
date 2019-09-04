<?php
namespace App\Console\Command\Internal;

use App\Service\Ftp;
use Azura\Console\Command\CommandAbstract;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class FtpAuth extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:ftp-auth')
            ->setDescription('Authenticate a user for PureFTPD');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $username = getenv('AUTHD_ACCOUNT');
        $password = getenv('AUTHD_PASSWORD');

        /** @var Ftp $ftp */
        $ftp = $this->get(Ftp::class);

        $ftp_output = $ftp->auth($username, $password);
        foreach ($ftp_output as $output_ln) {
            $output->writeln($output_ln);
        }
        return null;
    }
}
