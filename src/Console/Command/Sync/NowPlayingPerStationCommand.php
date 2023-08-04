<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Container\LoggerAwareTrait;
use App\Entity\Repository\StationRepository;
use App\Entity\Station;
use App\Sync\NowPlaying\Task\BuildQueueTask;
use App\Sync\NowPlaying\Task\NowPlayingTask;
use Monolog\LogRecord;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(
    name: 'azuracast:sync:nowplaying:station',
    description: 'Task to run the Now Playing worker task for a specific station.',
)]
final class NowPlayingPerStationCommand extends AbstractSyncCommand
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly StationRepository $stationRepo,
        private readonly BuildQueueTask $buildQueueTask,
        private readonly NowPlayingTask $nowPlayingTask
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('station', InputArgument::REQUIRED);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->logToExtraFile('app_nowplaying.log');

        $io = new SymfonyStyle($input, $output);
        $stationName = $input->getArgument('station');

        $station = $this->stationRepo->findByIdentifier($stationName);
        if (!($station instanceof Station)) {
            $io->error('Station not found.');
            return 1;
        }

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

        $this->logger->info('Now Playing sync task complete.');
        $this->logger->popProcessor();

        return 0;
    }
}
