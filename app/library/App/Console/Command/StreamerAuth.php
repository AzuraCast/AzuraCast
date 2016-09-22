<?php
namespace App\Console\Command;

use Entity\Station;
use Entity\StationStreamer;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

use App\Sync\Manager;

class StreamerAuth extends CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('streamer:auth')
            ->setDescription('Authorize a streamer to connect as a source for the radio service.')
            ->addArgument(
                'station_id',
                null,
                'The ID of the station.',
                null
            )->addArgument(
                'user',
                null,
                'The streamer username (or "shoutcast" for SC legacy auth).',
                null
            )->addArgument(
                'pass',
                null,
                'The streamer password (or "username:password" for SC legacy auth).',
                null
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $station_id = (int)$input->getArgument('station_id');
        $station = Station::find($station_id);

        if (!($station instanceof Station))
            return $this->_return($output, 'false');

        if ($input->getArgument('user') == 'shoutcast')
        {
            list($user, $pass) = explode(':', $input->getArgument('pass'));
        }
        else
        {
            $user = $input->getArgument('user');
            $pass = $input->getArgument('pass');
        }

        if (!$station->enable_streamers)
            return $this->_return($output, 'false');

        if (StationStreamer::authenticate($station, $user, $pass))
            return $this->_return($output, 'true');
        else
            return $this->_return($output, 'false');
    }

    protected function _return(OutputInterface $output, $result)
    {
        $output->write($result);
        return ($result == 'true');
    }
}