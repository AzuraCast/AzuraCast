<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Entity;
use App\Entity\Station;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:media:reprocess',
    description: 'Manually reload all media metadata from file.',
)]
final class ReprocessMediaCommand extends CommandAbstract
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly Entity\Repository\StationRepository $stationRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station-name', InputArgument::OPTIONAL);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationName = $input->getArgument('station-name');

        $io->title('Manually Reprocess Media');

        if (empty($stationName)) {
            $io->section('Reprocessing media for all stations...');

            $storageLocation = null;
        } else {
            $station = $this->stationRepo->findByIdentifier($stationName);
            if (!$station instanceof Station) {
                $io->error('Station not found.');
                return 1;
            }

            $storageLocation = $station->getMediaStorageLocation();

            $io->writeln(sprintf('Reprocessing media for station: %s', $station->getName()));
        }

        $reprocessMediaQueue = $this->em->createQueryBuilder()
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
