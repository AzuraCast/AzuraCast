<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Entity\Repository\SettingsRepository;
use App\Environment;
use App\LockFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'azuracast:sync:nowplaying',
    description: 'Task to run the Now Playing worker task.'
)]
class NowPlayingCommand extends CommandAbstract
{
    protected array $processes = [];

    public function __construct(
        protected EntityManagerInterface $em,
        protected SettingsRepository $settingsRepo,
        protected LockFactory $lockFactory,
        protected LoggerInterface $logger,
        protected Environment $environment,
    ) {
        parent::__construct();
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
            $io->error('Automated synchronization is temporarily disabled.');
            return 1;
        }

        $timeout = (int)$input->getOption('timeout');
        $this->loop($io, $timeout);

        return 0;
    }

    protected function loop(SymfonyStyle $io, int $timeout): void
    {
        $threshold = time() + $timeout;

        while (time() < $threshold || !empty($this->processes)) {
            // Check existing processes.
            foreach ($this->processes as $processName => $processGroup) {
                /** @var Lock $lock */
                $lock = $processGroup['lock'];

                /** @var Process $process */
                $process = $processGroup['process'];

                // 10% chance that refresh will be called
                if (\random_int(1, 100) <= 10) {
                    $lock->refresh();
                }

                if ($process->isRunning()) {
                    continue;
                }

                if ($process->isSuccessful()) {
                    $io->success('Task completed: ' . $processName);
                } else {
                    $io->error('Task failed: ' . $processName);
                }

                $lock->release();
                unset($this->processes[$processName]);
            }

            // Ensure a process is running for every active station.
            if (time() < $threshold - 5) {
                $activeStations = $this->em->createQuery(
                    <<<'DQL'
                SELECT s.id, s.short_name, s.nowplaying_timestamp
                FROM App\Entity\Station s
                WHERE s.is_enabled = 1
                DQL
                )->getArrayResult();

                foreach ($activeStations as $activeStation) {
                    $shortName = $activeStation['short_name'];

                    if (!isset($this->processes[$shortName])) {
                        $npTimestamp = (int)$activeStation['nowplaying_timestamp'];
                        if (time() > $npTimestamp + \random_int(5, 15)) {
                            $this->start($shortName, $io);
                        }
                    }
                }
            }

            $this->em->clear();
            gc_collect_cycles();
            \usleep(1500000);
        }
    }

    protected function start(
        string $shortName,
        SymfonyStyle $io
    ): void {
        $lockName = 'nowplaying_' . $shortName;

        $lock = $this->lockFactory->createAndAcquireLock($lockName, 30);
        if (false === $lock) {
            $this->logger->error(
                sprintf('Could not obtain lock for task "%s"; skipping it.', $shortName)
            );
            return;
        }

        $process = new Process([
            'php',
            $this->environment->getBaseDirectory() . '/bin/console',
            'azuracast:sync:nowplaying:station',
            $shortName,
        ], $this->environment->getBaseDirectory());

        $process->setTimeout(60);
        $process->setIdleTimeout(60);

        $stdout = [];
        $stderr = [];

        $io->info('Starting task: ' . $shortName);

        $process->run(function ($type, $data) use ($process, $io, &$stdout, &$stderr): void {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, getenv());

        $this->processes[$shortName] = [
            'process' => $process,
            'lock'    => $lock,
        ];
    }
}
