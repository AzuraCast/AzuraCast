<?php
namespace AzuraCast\Console\Command;

use Entity;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NextSong extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:next-song')
            ->setDescription('Return the next song to the AutoDJ.')
            ->addArgument(
                'station_id',
                InputArgument::REQUIRED,
                'The ID of the station.'
            )->addArgument(
                'simulate_autodj',
                InputArgument::OPTIONAL,
                'Force the AutoDJ to select a new song after executing this command.',
                false
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->di['em'];

        $station_id = (int)$input->getArgument('station_id');
        $station = $em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station)) {
            $output->write('false');
            return false;
        }

        /** @var Entity\Repository\SongHistoryRepository $history_repo */
        $history_repo = $em->getRepository(Entity\SongHistory::class);

        $as_autodj = (bool)$input->getArgument('simulate_autodj');

        /** @var Entity\SongHistory|null $sh */
        $sh = $history_repo->getNextSongForStation($station, $as_autodj);

        if ($sh instanceof Entity\SongHistory) {
            // 'annotate:type=\"song\",album=\"$ALBUM\",display_desc=\"$FULLSHOWNAME\",liq_start_next=\"2.5\",liq_fade_in=\"3.5\",liq_fade_out=\"3.5\":$SONGPATH'
            $song_path = $sh->getMedia()->getFullPath();
            $result = 'annotate:' . implode(',', $sh->getMedia()->getAnnotations()) . ':' . $song_path;
        } else {
            $result = APP_INCLUDE_ROOT . '/resources/error.mp3';
        }

        $output->write($result);
        return true;
    }
}