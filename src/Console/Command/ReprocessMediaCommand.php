<?php
namespace App\Console\Command;

use App\Entity;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ReprocessMediaCommand extends CommandAbstract
{
    public function __invoke(
        SymfonyStyle $io,
        EntityManager $em
    ) {
        $io->writeLn('Reloading all metadata for all media...');

        $stations = $em->getRepository(Entity\Station::class)->findAll();

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $em->getRepository(Entity\StationMedia::class);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $io->writeLn('Processing media for station: ' . $station->getName());

            foreach ($station->getMedia() as $media) {
                /** @var Entity\StationMedia $media */
                try {
                    $media_repo->processMedia($media, true);
                    $io->writeLn('Processed: ' . $media->getPath());
                } catch (Exception $e) {
                    $io->writeLn('Could not read source file for: ' . $media->getPath() . ' - ' . $e->getMessage());
                    continue;
                }
            }

            $em->flush();

            $io->writeLn('Station media reprocessed.');
        }

        return 0;
    }
}
