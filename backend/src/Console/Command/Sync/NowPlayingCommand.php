<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Cache\NowPlayingCache;
use App\Container\EntityManagerAwareTrait;
use App\Container\SettingsAwareTrait;
use App\Lock\LockFactory;
use App\Utilities\Types;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

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

        $settings = $this->readSettings();
        if ($settings->sync_disabled) {
            $this->logger->error('Automated synchronization is temporarily disabled.');
            return 1;
        }

        $timeout = Types::int($input->getOption('timeout'));
        $this->loop($io, $timeout);

        return 0;
    }

    private function loop(OutputInterface $output, int $timeout): void
    {
        $threshold = time() + $timeout;

        // If max current processes isn't specified, make it 1/3 of all stations, rounded up.
        $npMaxCurrentProcesses = $this->environment->getNowPlayingMaxConcurrentProcesses();
        if (null === $npMaxCurrentProcesses) {
            $npMaxCurrentProcesses = ceil(count($this->getStationsToRun($threshold)) / 3);
        }

        // Gate the Now Playing delay time between a reasonable minimum and maximum.
        $npDelayTime = max(
            min(
                $this->environment->getNowPlayingDelayTime() ?? 10,
                60
            ),
            5
        );

        while (time() < $threshold || !empty($this->processes)) {
            // Check existing processes.
            $this->checkRunningProcesses();

            // Only spawn new processes if we're before the timeout threshold and there are not too many processes.
            $numProcesses = count($this->processes);

            if (
                $numProcesses < $npMaxCurrentProcesses
                && time() < $threshold - 5
            ) {
                // Ensure a process is running for every active station.
                $npThreshold = time() - $npDelayTime - rand(0, 5);

                foreach ($this->getStationsToRun($npThreshold) as $shortName) {
                    if (count($this->processes) >= $npMaxCurrentProcesses) {
                        break;
                    }
                    if (isset($this->processes[$shortName])) {
                        continue;
                    }

                    $this->logger->debug('Starting NP process for station: ' . $shortName);

                    if ($this->start($output, $shortName)) {
                        usleep(100000);
                    }
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
            $lookup[$stationRow['short_name']] = $stationRow['updated_at'];
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
        OutputInterface $output,
        string $shortName
    ): bool {
        return $this->lockAndRunConsoleCommand(
            $output,
            $shortName,
            'nowplaying',
            [
                'azuracast:sync:nowplaying:station',
                $shortName,
            ]
        );
    }
}
