<?php
namespace App\Console\Command;

use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DjOn extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:djon')
            ->setDescription('Indicate that a DJ has begun streaming to a station.')
            ->addArgument(
                'station_id',
                InputArgument::REQUIRED,
                'The ID of the station.'
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

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            return 1;
        }

        /** @var Adapters $adapters */
        $adapters = $this->get(Adapters::class);

        $adapter = $adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $adapter->toggleLiveStatus($station, true);
        }

        $output->write('received');
        return 0;
    }
}
