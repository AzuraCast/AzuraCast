<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Sync\Task\NowPlaying;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Feedback extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:feedback')
            ->setDescription('Send upcoming song feedback from the AutoDJ back to AzuraCast.')
            ->addArgument(
                'station_id',
                InputArgument::REQUIRED,
                'The ID of the station.'
            )->addOption(
                'song',
                's',
                InputOption::VALUE_REQUIRED,
                'Song ID'
            )->addOption(
                'media',
                'm',
                InputOption::VALUE_OPTIONAL,
                'Media ID'
            )->addOption(
                'playlist',
                'p',
                InputOption::VALUE_OPTIONAL,
                'Playlist ID'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $station_id = (int)$input->getArgument('station_id');

        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        $station = $em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station)) {
            $output->write('false');
            return null;
        }

        try {
            /** @var NowPlaying $sync_nowplaying */
            $sync_nowplaying = $this->get(NowPlaying::class);

            $sync_nowplaying->queueStation($station, [
                'song_id' => $input->getOption('song'),
                'media_id' => $input->getOption('media'),
                'playlist_id' => $input->getOption('playlist'),
            ]);

            $output->write('OK');
            return null;
        } catch (Exception $e) {
            $output->write('Error: ' . $e->getMessage());
            return 1;
        }
    }
}
