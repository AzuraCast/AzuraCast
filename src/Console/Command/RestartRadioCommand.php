<?php
namespace App\Console\Command;

use App\Entity\Station;
use App\Radio\Configuration;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestartRadioCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Configuration $configuration
    ) {
        $io->section('Restarting all radio stations...');

        /** @var Station[] $stations */
        $stations = $em->getRepository(Station::class)->findAll();

        $io->progressStart(count($stations));

        foreach ($stations as $station) {
            $configuration->writeConfiguration($station, false, true);

            $station->setHasStarted(true);
            $station->setNeedsRestart(false);

            $em->persist($station);
            $em->flush($station);

            $io->progressAdvance();
        }

        $io->progressFinish();
        return 0;
    }
}
