<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Environment;
use App\Entity\Repository\SettingsRepository;
use App\Lock\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
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
final class NowPlayingCommand extends AbstractSyncCommand
{
    public final const MAX_CONCURRENT_PROCESSES = 5;

    public function __construct(
        LoggerInterface $logger,
        LockFactory $lockFactory,
        Environment $environment,
        private readonly EntityManagerInterface $em,
        private readonly SettingsRepository $settingsRepo,
    ) {
        parent::__construct($logger, $lockFactory, $environment);
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
        $io = new SymfonyStyle($input, $output);

        $settings = $this->settingsRepo->readSettings();
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
                $numProcesses < self::MAX_CONCURRENT_PROCESSES
                && time() < $threshold - 5
            ) {
                $numRemaining = self::MAX_CONCURRENT_PROCESSES - $numProcesses;

                // Ensure a process is running for every active station.
                $activeStations = $this->em->createQuery(
                    <<<'DQL'
                    SELECT s.short_name
                    FROM App\Entity\Station s
                    WHERE s.is_enabled = 1 AND s.has_started = 1
                    AND s.short_name NOT IN (:currentProcesses)
                    AND s.nowplaying_timestamp < :threshold
                    ORDER BY s.nowplaying_timestamp ASC
                    DQL
                )->setParameter('currentProcesses', array_keys($this->processes))
                    ->setParameter('threshold', time() - $this->environment->getNowPlayingDelayTime())
                    ->setMaxResults($numRemaining)
                    ->getSingleColumnResult();

                foreach ($activeStations as $shortName) {
                    $this->start($io, $shortName);
                    usleep(250000);
                }
            }

            $this->em->clear();
            gc_collect_cycles();
            usleep(1000000);
        }
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
