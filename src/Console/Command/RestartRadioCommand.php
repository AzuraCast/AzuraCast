<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Radio\Configuration;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RestartRadioCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        StationRepository $stationRepo,
        Configuration $configuration,
        ?string $stationName = null
    ): int {
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
            $stations = $stationRepo->fetchAll();
        }

        $io->progressStart(count($stations));

        foreach ($stations as $station) {
            $configuration->writeConfiguration($station, true);
            $io->progressAdvance();
        }

        $io->progressFinish();
        return 0;
    }
}
