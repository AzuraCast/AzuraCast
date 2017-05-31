<?php
namespace AzuraCast\Console\Command;

use Entity;
use Doctrine\ORM\EntityManager;
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
                null,
                'The ID of the station.',
                null
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

        /** @var Entity\Repository\StationMediaRepository $media_repo */
        $media_repo = $em->getRepository(Entity\StationMedia::class);

        $result = $media_repo->getNextSong($station);
        $output->write($result);
        return true;
    }
}