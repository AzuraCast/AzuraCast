<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Sync\NowPlaying\Task\BuildQueueTask;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'azuracast:sync:nowplaying:station',
    description: 'Task to run the Now Playing worker task for a specific station.',
)]
class NowPlayingPerStationCommand extends CommandAbstract
{
    protected array $processes = [];

    public function __construct(
        protected StationRepository $stationRepo,
        protected BuildQueueTask $buildQueueTask,
        protected NowPlayingTask $nowPlayingTask,
    ) {
        parent::__construct();
    }

    protected function configure()
    {
        $this->addArgument('station', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stationName = $input->getArgument('station');

        $station = $this->stationRepo->findByIdentifier($stationName);
        if (!($station instanceof Station)) {
            $io->error('Station not found.');
            return 1;
        }

        $this->nowPlayingTask->run($station);
        $this->buildQueueTask->run($station);
        return 0;
    }
}
