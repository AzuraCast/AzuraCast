<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Container\EnvironmentAwareTrait;
use App\Container\LoggerAwareTrait;
use App\Lock\LockFactory;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\Lock;
use Symfony\Component\Process\Process;

use function random_int;

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
            if (random_int(1, 100) <= 10) {
                $lock->refresh();
            }

            if ($process->isRunning()) {
                continue;
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
        SymfonyStyle $io,
        string $processKey,
        string $lockPrefix,
        array $consoleCommand,
        int $timeout = 60
    ): void {
        $lockName = $lockPrefix . '_' . $processKey;

        $lock = $this->lockFactory->createAndAcquireLock($lockName, $timeout);
        if (false === $lock) {
            $this->logger->error(
                sprintf('Could not obtain lock for task "%s"; skipping it.', $processKey)
            );
            return;
        }

        $process = new Process(
            array_merge(
                [
                    'php',
                    $this->environment->getBaseDirectory() . '/bin/console',
                ],
                $consoleCommand
            ),
            $this->environment->getBaseDirectory()
        );

        $process->setTimeout($timeout);
        $process->setIdleTimeout($timeout);

        $stdout = [];
        $stderr = [];

        $process->run(function ($type, $data) use ($process, $io, &$stdout, &$stderr): void {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, getenv());

        $this->processes[$processKey] = [
            'process' => $process,
            'lock' => $lock,
        ];
    }
}
