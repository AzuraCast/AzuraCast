<?php

declare(strict_types=1);

namespace App\Console\Command\Sync;

use App\Console\Command\CommandAbstract;
use App\Event\GetSyncTasks;
use App\Sync\Task\ScheduledTaskInterface;
use Carbon\CarbonImmutable;
use Cron\CronExpression;
use Psr\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'azuracast:sync:nowplaying',
    description: 'Task to run the Now Playing worker task.'
)]
class NowPlayingCommand extends CommandAbstract
{
    protected array $processes = [];

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $syncTasksEvent = new GetSyncTasks();
        $this->dispatcher->dispatch($syncTasksEvent);

        $now = CarbonImmutable::now(new \DateTimeZone('UTC'));

        /** @var class-string<ScheduledTaskInterface> $taskClass */
        foreach ($syncTasksEvent->getTasks() as $taskClass) {
            $schedulePattern = $taskClass::getSchedulePattern();
            $cronExpression = new CronExpression($schedulePattern);

            if ($cronExpression->isDue($now)) {
            }
        }

        $this->manageStartedEvents();
        return 0;
    }

    protected function start(
        string $taskClass,
        OutputInterface $output
    ): void {
        set_time_limit($timeout);

        if (is_array($cmd)) {
            $process = new Process($cmd, $cwd);
        } else {
            $process = Process::fromShellCommandline($cmd, $cwd);
        }

        $process->setTimeout($timeout - 60);
        $process->setIdleTimeout(3600);

        $stdout = [];
        $stderr = [];

        $process->mustRun(function ($type, $data) use ($process, $io, &$stdout, &$stderr): void {
            if ($process::ERR === $type) {
                $io->getErrorStyle()->write($data);
                $stderr[] = $data;
            } else {
                $io->write($data);
                $stdout[] = $data;
            }
        }, $env);

        $this->logger = $this->loggerFactory
            ->create();

        // if sendOutputTo or appendOutputTo have been specified
        if (!$event->nullOutput()) {
            // if sendOutputTo then truncate the log file if it exists
            if (!$event->shouldAppendOutput) {
                $f = @\fopen($event->output, 'r+');
                if (false !== $f) {
                    \ftruncate($f, 0);
                    \fclose($f);
                }
            }
            // Create an instance of the Logger specific to the event
            $event->logger = $this->loggerFactory->createEvent($event->output);
        }

        $this->consoleLogger
            ->debug("Invoke Event's ping before.");

        $this->pingBefore($event);

        // Running the before-callbacks
        $event->outputStream = ($this->invoke($event->beforeCallbacks()));
        $event->start();
    }

    protected function manageStartedEvents(): void
    {
        while ($this->schedules) {
            foreach ($this->schedules as $scheduleKey => $schedule) {
                $events = $schedule->events();
                // 10% chance that refresh will be called
                $refreshLocks = (\mt_rand(1, 100) <= 10);

                /** @var Event $event */
                foreach ($events as $eventKey => $event) {
                    if ($refreshLocks) {
                        $event->refreshLock();
                    }

                    $proc = $event->getProcess();
                    if ($proc->isRunning()) {
                        continue;
                    }

                    $runStatus = '';

                    if ($proc->isSuccessful()) {
                        $this->consoleLogger
                            ->debug("Invoke Event's ping after.");
                        $this->pingAfter($event);

                        $runStatus = '<info>success</info>';

                        $event->outputStream .= $event->wholeOutput();
                        $event->outputStream .= $this->invoke($event->afterCallbacks());

                        $this->handleOutput($event);
                    } else {
                        $runStatus = '<error>fail</error>';

                        // Invoke error callbacks
                        $this->invoke($event->errorCallbacks());
                        // Calling registered error callbacks with an instance of $event as argument
                        $this->invoke($schedule->errorCallbacks(), [$event]);
                        $this->handleError($event);
                    }

                    $id = $event->description ?: $event->getId();

                    $this->consoleLogger
                        ->debug("Task <info>${id}</info> status: {$runStatus}.");

                    // Dismiss the event if it's finished
                    $schedule->dismissEvent($eventKey);
                }

                // If there's no event left for the Schedule instance,
                // run the schedule's after-callbacks and remove
                // the Schedule from list of active schedules.                                                                                                                           zzzwwscxqqqAAAQ11
                if (!\count($schedule->events())) {
                    $this->consoleLogger
                        ->debug("Invoke Schedule's ping after.");

                    $this->pingAfter($schedule);
                    $this->invoke($schedule->afterCallbacks());
                    unset($this->schedules[$scheduleKey]);
                }
            }

            \usleep(250000);
        }
    }
}
