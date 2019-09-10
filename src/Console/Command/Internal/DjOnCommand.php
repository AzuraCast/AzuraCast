<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Nette\DI\Config\Adapter;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DjOnCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Adapters $adapters,
        int $stationId
    ) {
        $station = $em->find(Entity\Station::class, $stationId);

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            return 1;
        }

        $adapter = $adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $adapter->toggleLiveStatus($station, true);
        }

        $io->write('received');
        return 0;
    }
}
