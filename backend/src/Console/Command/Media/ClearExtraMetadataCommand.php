<?php

declare(strict_types=1);

namespace App\Console\Command\Media;

use App\Entity\StationMedia;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:media:clear-extra',
    description: 'Clear all extra metadata from the specified media.',
)]
final class ClearExtraMetadataCommand extends AbstractBatchMediaCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $station = $this->getStation($input);
        $path = $this->getPath($input);

        $io->title('Clear Extra Metadata');

        $reprocessMediaQueue = $this->em->createQueryBuilder()
            ->update(StationMedia::class, 'sm')
            ->set('sm.mtime', 0)
            ->set('sm.extra_metadata_raw', 'null');

        if (null === $station) {
            $io->section('Clearing extra metadata for all stations...');
        } else {
            $io->writeln(sprintf('Clearing extra metadata for station: %s', $station->name));

            $reprocessMediaQueue = $reprocessMediaQueue->andWhere('sm.storage_location = :storageLocation')
                ->setParameter('storageLocation', $station->media_storage_location);
        }

        if (null !== $path) {
            $reprocessMediaQueue = $reprocessMediaQueue->andWhere('sm.path LIKE :path')
                ->setParameter('path', $path . '%');
        }

        $recordsAffected = $reprocessMediaQueue->getQuery()->getSingleScalarResult();

        $io->writeln(sprintf('Cleared extra metadata for %d record(s).', $recordsAffected));

        return 0;
    }
}
