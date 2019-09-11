<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DjAuthCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Adapters $adapters,
        int $stationId,
        string $djUser = '',
        string $djPassword = ''
    ) {
        $station = $em->getRepository(Entity\Station::class)->find($stationId);

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            $io->write('false');
            return null;
        }

        $adapter = $adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $response = $adapter->authenticateStreamer($station, $djUser, $djPassword);
            $io->write($response);
            return null;
        }

        $io->write('false');
        return null;
    }
}
