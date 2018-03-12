<?php
namespace AzuraCast\Console\Command;

use AzuraCast\Radio\Adapters;
use AzuraCast\Radio\Backend\Liquidsoap;
use Doctrine\ORM\EntityManager;
use Entity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class StreamerAuth extends \App\Console\Command\CommandAbstract
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
        $em = $this->di[EntityManager::class];

        $station = $em->getRepository(Entity\Station::class)->find($station_id);

        if (!($station instanceof Entity\Station) || !$station->getEnableStreamers()) {
            $output->write('false');
            return false;
        }

        $user = $input->getOption('dj_user');
        $pass = $input->getOption('dj_password');

        /** @var Adapters $adapters */
        $adapters = $this->di[Adapters::class];

        $adapter = $adapters->getBackendAdapter($station);

        if ($adapter instanceof Liquidsoap) {
            $response = $adapter->authenticateStreamer($user, $pass);
            $output->write($response);
            return ($response === 'true');
        }

        $output->write('false');
        return false;
    }
}