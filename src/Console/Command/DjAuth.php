<?php
namespace App\Console\Command;

use App\Radio\Adapters;
use App\Radio\Backend\Liquidsoap;
use Azura\Console\Command\CommandAbstract;
use Doctrine\ORM\EntityManager;
use App\Entity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DjAuth extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:auth')
            ->setDescription('Authorize a streamer to connect as a source for the radio service.')
            ->addArgument(
                'station_id',
                InputArgument::REQUIRED,
                'The ID of the station.'
            )->addOption(
                'dj_user',
                null,
                InputOption::VALUE_REQUIRED,
                'The streamer username (or "shoutcast" for SC legacy auth).'
            )->addOption(
                'dj_password',
                null,
                InputOption::VALUE_REQUIRED,
                'The streamer password (or "username:password" for SC legacy auth).'
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
            $output->write('false');
            return null;
        }

        $user = $input->getOption('dj_user');
        $pass = $input->getOption('dj_password');

        /** @var Adapters $adapters */
        $adapters = $this->get(Adapters::class);

        $adapter = $adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $response = $adapter->authenticateStreamer($station, $user, $pass);
            $output->write($response);
            return null;
        }

        $output->write('false');
        return null;
    }
}
