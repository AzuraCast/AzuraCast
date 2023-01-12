<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Doctrine\ReloadableEntityManagerInterface;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Sync\NowPlaying\Task\BuildQueueTask;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Monolog\Logger;
use Monolog\LogRecord;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'azuracast:sync:nowplaying',
    description: 'Task to run the Now Playing worker task for a specific station.',
)]
final class NowPlayingCommand extends CommandAbstract
{
    public function __construct(
        private readonly ReloadableEntityManagerInterface $em,
        private readonly StationRepository $stationRepo,
        private readonly BuildQueueTask $buildQueueTask,
        private readonly NowPlayingTask $nowPlayingTask,
        private readonly Logger $logger,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station', InputArgument::REQUIRED);

        $this->addOption(
            'timeout',
            't',
            InputOption::VALUE_OPTIONAL,
            'Amount of time (in seconds) to run the worker process.',
            600
        );
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

        $timeout = (int)$input->getOption('timeout');

        $this->logger->pushProcessor(
            function (LogRecord $record) use ($station) {
                $record->extra['station'] = [
                    'id' => $station->getId(),
                    'name' => $station->getName(),
                ];
                return $record;
            }
        );

        $this->logger->info('Starting Now Playing sync task.');

        $this->loop($station, $timeout);

        $this->logger->info('Now Playing sync task complete.');
        $this->logger->popProcessor();

        return 0;
    }

    private function loop(Station $station, int $timeout): void
    {
        $threshold = time() + $timeout;

        while (time() < $threshold) {
            $station = $this->em->refetch($station);

            try {
                $this->buildQueueTask->run($station);
            } catch (Throwable $e) {
                $this->logger->error(
                    'Queue builder error: ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }

            try {
                $this->nowPlayingTask->run($station);
            } catch (Throwable $e) {
                $this->logger->error(
                    'Now Playing error: ' . $e->getMessage(),
                    ['exception' => $e]
                );
            }

            $this->em->clear();
            gc_collect_cycles();
            usleep(5000000);
        }
    }
}
