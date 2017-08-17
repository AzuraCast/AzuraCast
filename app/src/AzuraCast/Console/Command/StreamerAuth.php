<?php
namespace AzuraCast\Console\Command;

use App\Sync\Manager;
use Entity\Station;
use Entity\StationStreamer;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class StreamerAuth extends \App\Console\Command\CommandAbstract
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('azuracast:internal:streamer-auth')
            ->setDescription('Authorize a streamer to connect as a source for the radio service.')
            ->addArgument(
                'station_id',
                InputArgument::REQUIRED,
                'The ID of the station.'
            )->addArgument(
                'user',
                InputArgument::REQUIRED,
                'The streamer username (or "shoutcast" for SC legacy auth).'
            )->addArgument(
                'pass',
                InputArgument::REQUIRED,
                'The streamer password (or "username:password" for SC legacy auth).'
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $station_id = (int)$input->getArgument('station_id');
        $station = $this->di['em']->getRepository(Station::class)->find($station_id);

        if (!($station instanceof Station)) {
            return $this->_return($output, 'false');
        }

        if ($input->getArgument('user') == 'shoutcast') {
            list($user, $pass) = explode(':', $input->getArgument('pass'));
        } else {
            $user = $input->getArgument('user');
            $pass = $input->getArgument('pass');
        }

        if (!$station->getEnableStreamers()) {
            return $this->_return($output, 'false');
        }

        $fe_config = (array)$station->getFrontendConfig();
        if (!empty($fe_config['source_pw']) && strcmp($fe_config['source_pw'], $pass) === 0) {
            return $this->_return($output, 'true');
        }

        if ($this->di['em']->getRepository(StationStreamer::class)->authenticate($station, $user, $pass)) {
            return $this->_return($output, 'true');
        } else {
            return $this->_return($output, 'false');
        }
    }

    protected function _return(OutputInterface $output, $result)
    {
        $output->write($result);

        return ($result == 'true');
    }
}