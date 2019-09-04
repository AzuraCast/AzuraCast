<?php
namespace App\Console\Command;

use App\Entity;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ReprocessMedia extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:media:reprocess')
            ->setDescription('Manually reload all media metadata from file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeLn('Reloading all metadata for all media...');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        $stations = $em->getRepository(Entity\Station::class)->findAll();

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $em->getRepository(Entity\StationMedia::class);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */
            $output->writeLn('Processing media for station: ' . $station->getName());

            foreach ($station->getMedia() as $media) {
                /** @var Entity\StationMedia $media */
                try {
                    $media_repo->processMedia($media, true);
                    $output->writeLn('Processed: ' . $media->getPath());
                } catch (Exception $e) {
                    $output->writeLn('Could not read source file for: ' . $media->getPath() . ' - ' . $e->getMessage());
                    continue;
                }
            }

            $em->flush();

            $output->writeLn('Station media reprocessed.');
        }

        return 0;
    }
}
