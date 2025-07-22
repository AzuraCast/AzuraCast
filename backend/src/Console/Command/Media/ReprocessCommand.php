<?php

declare(strict_types=1);

namespace App\Console\Command\Media;

use App\Entity\StationMedia;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:media:reprocess',
    description: 'Manually reload all media metadata from file.',
)]
final class ReprocessCommand extends AbstractBatchMediaCommand
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $station = $this->getStation($input);
        $path = $this->getPath($input);

        $io->title('Manually Reprocess Media');

        $reprocessMediaQueue = $this->em->createQueryBuilder()
            ->update(StationMedia::class, 'sm')
            ->set('sm.mtime', 0);

        if (null === $station) {
            $io->section('Reprocessing media for all stations...');
        } else {
            $io->writeln(sprintf('Reprocessing media for station: %s', $station->name));

            $reprocessMediaQueue = $reprocessMediaQueue->andWhere('sm.storage_location = :storageLocation')
                ->setParameter('storageLocation', $station->media_storage_location);
        }

        if (null !== $path) {
            $reprocessMediaQueue = $reprocessMediaQueue->andWhere('sm.path LIKE :path')
                ->setParameter('path', $path . '%');
        }

        $recordsAffected = $reprocessMediaQueue->getQuery()->getSingleScalarResult();

        $io->writeln(sprintf('Marked %d record(s) for reprocessing.', $recordsAffected));

        return 0;
    }
}
