<?php
namespace AzuraCast\Console\Command;

use AzuraCast\Radio\Adapters;
use AzuraCast\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManager;
use Entity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DjOn extends \App\Console\Command\CommandAbstract
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
            $adapter->toggleLiveStatus(true);
        }

        $output->write('received');
        return 0;
    }
}