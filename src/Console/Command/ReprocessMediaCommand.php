<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReprocessMediaCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManagerInterface $em,
        StationRepository $stationRepo,
        ?string $stationName = null
    ): int {
        $io->title('Manually Reprocess Media');

        if (empty($stationName)) {
            $io->section('Reprocessing media for all stations...');

            $storageLocation = null;
        } else {
            $station = $stationRepo->findByIdentifier($stationName);
            if (!$station instanceof Station) {
                $io->error('Station not found.');
                return 1;
            }

            $storageLocation = $station->getMediaStorageLocation();

            $io->writeln(sprintf('Reprocessing media for station: %s', $station->getName()));
        }

        $reprocessMediaQueue = $em->createQueryBuilder()
            ->update(Entity\StationMedia::class, 'sm')
            ->set('sm.mtime', 'NULL');

        if (null !== $storageLocation) {
            $reprocessMediaQueue = $reprocessMediaQueue->where('sm.storage_location = :storageLocation')
                ->setParameter('storageLocation', $storageLocation);
        }

        $recordsAffected = $reprocessMediaQueue->getQuery()->getSingleScalarResult();

        $io->writeln(sprintf('Marked %d records for reprocessing.', $recordsAffected));

        return 0;
    }
}
