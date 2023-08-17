<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Cache\NowPlayingCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Lock\LockFactory;
use App\Service\HighAvailability;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

use function random_int;

#[AsCommand(
    name: 'azuracast:sync:nowplaying',
    description: 'Task to run the Now Playing worker task.'
)]
final class NowPlayingCommand extends AbstractSyncRunnerCommand
{
    use EntityManagerAwareTrait;
    use SettingsAwareTrait;

    public function __construct(
        private readonly NowPlayingCache $nowPlayingCache,
        private readonly HighAvailability $highAvailability,
        LockFactory $lockFactory,
    ) {
        parent::__construct($lockFactory);
    }

    protected function configure(): void
    {
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
        $this->logToExtraFile('app_nowplaying.log');

        $io = new SymfonyStyle($input, $output);

        if (!$this->highAvailability->isActiveServer()) {
            $this->logger->error('This instance is not the current active instance.');
            sleep(30);
            return 0;
        }

        $settings = $this->readSettings();
        if ($settings->getSyncDisabled()) {
            $this->logger->error('Automated synchronization is temporarily disabled.');
            return 1;
        }

        $timeout = (int)$input->getOption('timeout');
        $this->loop($io, $timeout);

        return 0;
    }

    private function loop(SymfonyStyle $io, int $timeout): void
    {
        $threshold = time() + $timeout;

        while (time() < $threshold || !empty($this->processes)) {
            // Check existing processes.
            $this->checkRunningProcesses();

            $numProcesses = count($this->processes);

            if (
                $numProcesses < $this->environment->getNowPlayingMaxConcurrentProcesses()
                && time() < $threshold - 5
            ) {
                // Ensure a process is running for every active station.
                $npDelay = max(min($this->environment->getNowPlayingDelayTime(), 60), 5);
                $npThreshold = time() - $npDelay - random_int(0, 5);

                foreach ($this->getStationsToRun($npThreshold) as $shortName) {
                    if (count($this->processes) >= $this->environment->getNowPlayingMaxConcurrentProcesses()) {
                        break;
                    }
                    if (isset($this->processes[$shortName])) {
                        continue;
                    }

                    $this->logger->debug('Starting NP process for station: ' . $shortName);

                    $this->start($io, $shortName);
                    usleep(250000);
                }
            }

            $this->em->clear();
            gc_collect_cycles();
            usleep(1000000);
        }
    }

    private function getStationsToRun(
        int $threshold
    ): array {
        $lookupRaw = $this->nowPlayingCache->getLookup();
        $lookup = [];
        foreach ($lookupRaw as $stationRow) {
            $lookup[$stationRow['short_name']] = (int)($stationRow['updated_at'] ?? 0);
        }

        $allStations = $this->em->createQuery(
            <<<'DQL'
            SELECT s.short_name
            FROM App\Entity\Station s
            WHERE s.is_enabled = 1 AND s.has_started = 1
            DQL
        )->getSingleColumnResult();

        $stationsByUpdated = [];
        foreach ($allStations as $shortName) {
            $stationsByUpdated[$shortName] = $lookup[$shortName] ?? 0;
        }

        asort($stationsByUpdated, SORT_NUMERIC);

        return array_keys(
            array_filter(
                $stationsByUpdated,
                fn($timestamp) => $timestamp < $threshold
            )
        );
    }

    private function start(
        SymfonyStyle $io,
        string $shortName
    ): void {
        $this->lockAndRunConsoleCommand(
            $io,
            $shortName,
            'nowplaying',
            [
                'azuracast:sync:nowplaying:station',
                $shortName,
            ]
        );
    }
}
