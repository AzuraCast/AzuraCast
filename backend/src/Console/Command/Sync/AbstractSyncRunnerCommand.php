<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Lock\LockFactory;
use Symfony\Component\Console\Output\ConsoleOutputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

abstract class AbstractSyncRunnerCommand extends AbstractSyncCommand
{
    use LoggerAwareTrait;
    use EnvironmentAwareTrait;

    protected array $processes = [];

    public function __construct(
        protected LockFactory $lockFactory,
    ) {
        parent::__construct();
    }

    protected function checkRunningProcesses(): void
    {
        foreach ($this->processes as $processName => $processGroup) {
            /** @var Lock $lock */
            $lock = $processGroup['lock'];

            /** @var Process $process */
            $process = $processGroup['process'];

            // 10% chance that refresh will be called
            if (rand(1, 100) <= 10) {
                $lock->refresh();
            }

            if ($process->isRunning()) {
                // Process::setTimeout()/setIdleTimeout() are only enforced when checkTimeout() is
                // called; isRunning() alone never does. Without this, a child that hangs (e.g. after
                // an InnoDB deadlock in the queue builder) runs forever while its lock is refreshed
                // above, and every later run logs "Could not obtain lock" until someone kills it.
                try {
                    $process->checkTimeout();
                    continue;
                } catch (ProcessTimedOutException $e) {
                    $this->logger->error(
                        sprintf(
                            'Sync process %s exceeded its timeout (%s); stopping it and releasing its lock.',
                            $processName,
                            $e->getMessage()
                        )
                    );
                    $process->stop(5);
                    $lock->release();
                    unset($this->processes[$processName]);
                    continue;
                }
            }

            $this->logger->debug(sprintf(
                'Sync process %s ended with status code %d.',
                $processName,
                $process->getExitCode()
            ));

            $lock->release();
            unset($this->processes[$processName]);
        }
    }

    protected function lockAndRunConsoleCommand(
        OutputInterface $output,
        string $processKey,
        string $lockPrefix,
        array $consoleCommand,
        int $timeout = 60
    ): bool {
        $lockName = $lockPrefix . '_' . $processKey;

        $lock = $this->lockFactory->createAndAcquireLock($lockName, $timeout);
        if (false === $lock) {
            $this->logger->error(
                sprintf('Could not obtain lock for task "%s"; skipping it.', $processKey)
            );
            return false;
        }

        $process = new Process(
            array_merge(
                [
                    'php',
                    $this->environment->getBackendDirectory() . '/bin/console',
                ],
                $consoleCommand
            ),
            $this->environment->getBaseDirectory()
        );

        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);

        $stderr = match (true) {
            $output instanceof SymfonyStyle => $output->getErrorStyle(),
            $output instanceof ConsoleOutputInterface => $output->getErrorOutput(),
            default => $output
        };

        $process->start(function ($type, $data) use ($output, $stderr): void {
            if (Process::ERR === $type) {
                $stderr->write($data);
            } else {
                $output->write($data);
            }
        }, getenv());

        $this->processes[$processKey] = [
            'process' => $process,
            'lock' => $lock,
        ];

        return true;
    }
}
