<?php

namespace App\Console\Command;

use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReprocessMediaCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        StationRepository $stationRepo,
        Entity\Repository\StationMediaRepository $media_repo,
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

        foreach ($stations as $station) {
            $io->writeln('Processing media for station: ' . $station->getName());

            foreach ($station->getMedia() as $media) {
                /** @var Entity\StationMedia $media */
                try {
                    $media_repo->processMedia($media, true);
                    $io->writeln('Processed: ' . $media->getPath());
                } catch (Exception $e) {
                    $io->writeln('Could not read source file for: ' . $media->getPath() . ' - ' . $e->getMessage());
                    continue;
                }
            }

            $em->flush();

            $io->writeln('Station media reprocessed.');
        }

        return 0;
    }
}
