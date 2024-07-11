<?php

declare(strict_types=1);

namespace App\Console\Command;

use App\Container\EntityManagerAwareTrait;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Entity\StationMedia;
use App\Utilities\Types;
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
    use EntityManagerAwareTrait;

    public function __construct(
        private readonly StationRepository $stationRepo,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            'station-name',
            InputArgument::OPTIONAL,
            'The shortcode for the station (i.e. "my_station_name") to only process one station.'
        );
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Optionally specify a path (of either a file or a directory) to only process that item.'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $stationName = Types::stringOrNull($input->getArgument('station-name'), true);
        $path = Types::stringOrNull($input->getArgument('path'), true);

        $io->title('Manually Reprocess Media');

        $reprocessMediaQueue = $this->em->createQueryBuilder()
            ->update(StationMedia::class, 'sm')
            ->set('sm.mtime', 0);

        if (null === $stationName) {
            $io->section('Reprocessing media for all stations...');
        } else {
            $station = $this->stationRepo->findByIdentifier($stationName);
            if (!$station instanceof Station) {
                $io->error('Station not found.');
                return 1;
            }

            $storageLocation = $station->getMediaStorageLocation();

            $io->writeln(sprintf('Reprocessing media for station: %s', $station->getName()));

            $reprocessMediaQueue = $reprocessMediaQueue->andWhere('sm.storage_location = :storageLocation')
                ->setParameter('storageLocation', $storageLocation);
        }

        if (null !== $path) {
            $reprocessMediaQueue = $reprocessMediaQueue->andWhere('sm.path LIKE :path')
                ->setParameter('path', $path . '%');
        }

        $recordsAffected = $reprocessMediaQueue->getQuery()->getSingleScalarResult();

        $io->writeln(sprintf('Marked %d records for reprocessing.', $recordsAffected));

        return 0;
    }
}
