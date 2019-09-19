<?php
namespace App\Console\Command;

use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Radio\Configuration;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestartRadioCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em,
        Configuration $configuration,
        ?string $stationName = null
    ) {
        /** @var StationRepository $stationRepo */
        $stationRepo = $em->getRepository(Station::class);

        if (!empty($stationName)) {
            $station = $stationRepo->findByIdentifier($stationName);

            if (!$station instanceof Station) {
                $io->error('Station not found.');
                return 1;
            }

            $stations = [$station];
        } else {
            $io->section('Restarting all radio stations...');

            /** @var Station[] $stations */
            $stations = $stationRepo->findAll();
        }

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
