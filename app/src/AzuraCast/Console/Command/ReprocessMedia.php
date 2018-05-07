<?php
namespace AzuraCast\Console\Command;

use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Entity;

class ReprocessMedia extends \App\Console\Command\CommandAbstract
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
        $song_repo = $em->getRepository(Entity\Song::class);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */

            $output->writeLn('Processing media for station: ' . $station->getName());

            foreach($station->getMedia() as $media) {
                /** @var Entity\StationMedia $media */

                try {
                    if (empty($media->getUniqueId())) {
                        $media->generateUniqueId();
                    }

                    $song_info = $media->loadFromFile(true);
                    if (!empty($song_info)) {
                        $media->setSong($song_repo->getOrCreate($song_info));
                    }

                    $em->persist($media);

                    $output->writeLn('Processed: '.$media->getFullPath());
                } catch (\Exception $e) {
                    $output->writeLn('Could not read source file for: '.$media->getFullPath().' - '.$e->getMessage());
                    continue;
                }
            }

            $em->flush();

            $output->writeLn('Station media reprocessed.');
        }

        return 0;
    }
}