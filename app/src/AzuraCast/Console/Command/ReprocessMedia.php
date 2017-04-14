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
        $this->setName('media:reprocess')
            ->setDescription('Manually reload all media metadata from file.');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        \App\Debug::setEchoMode(true);

        \App\Debug::log('Reloading all metadata for all media...');
        \App\Debug::divider();

        /** @var EntityManager $em */
        $em = $this->di['em'];

        $stations = $em->getRepository(Entity\Station::class)->findAll();
        $song_repo = $em->getRepository(Entity\Song::class);

        foreach ($stations as $station) {
            /** @var Entity\Station $station */

            \App\Debug::log('Processing media for station: ' . $station->name);

            foreach($station->media as $media) {
                /** @var Entity\StationMedia $media */

                try {
                    $song_info = $media->loadFromFile(true);
                    if (!empty($song_info)) {
                        $media->song = $song_repo->getOrCreate($song_info);
                    }

                    $em->persist($media);

                    \App\Debug::log('Processed: '.$media->getFullPath());
                } catch (\Exception $e) {
                    \App\Debug::log('Could not read source file for: '.$media->getFullPath());
                    continue;
                }
            }

            $em->flush();

            \App\Debug::log('Station media reprocessed.');
            \App\Debug::divider();
        }
    }
}