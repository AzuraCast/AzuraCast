<?php
namespace App\Console\Command\Internal;

use App\Entity;
use App\Radio\AutoDJ;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class NextSong extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:nextsong')
            ->setDescription('Return the next song to the AutoDJ.')
            ->addArgument(
                'station_id',
                InputArgument::REQUIRED,
                'The ID of the station.'
            )->addArgument(
                'as_autodj',
                InputArgument::OPTIONAL,
                'Force the AutoDJ to select a new song after executing this command.',
                true
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        /** @var EntityManager $em */
        $em = $this->get(EntityManager::class);

        $station_id = (int)$input->getArgument('station_id');
        $station = $em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station)) {
            $output->write('false');
            return null;
        }

        $as_autodj = ($input->getArgument('as_autodj') !== 'false');

        /** @var AutoDJ $autodj */
        $autodj = $this->get(AutoDJ::class);

        $output->write($autodj->annotateNextSong($station, $as_autodj));
        return null;
    }
}
