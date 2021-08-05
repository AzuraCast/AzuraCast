<?php

declare(strict_types=1);

namespace App\Sync;

use App\Entity\Repository\SettingsRepository;
use App\Environment;
use App\Event\GetSyncTasks;
use App\LockFactory;
use App\Message;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use InvalidArgumentException;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LogLevel;

/**
 * The runner of scheduled synchronization tasks.
 */
class Runner
{
    public function __construct(
        protected SettingsRepository $settingsRepo,
        protected Environment $environment,
        protected Logger $logger,
        protected LockFactory $lockFactory,
        protected EventDispatcherInterface $eventDispatcher,
        protected EntityManagerInterface $em
    ) {
    }

    public function __invoke(Message\AbstractMessage $message): void
    {
        if ($message instanceof Message\RunSyncTaskMessage) {
            $outputPath = $message->outputPath;

            if (null !== $outputPath) {
                $logHandler = new StreamHandler($outputPath, LogLevel::DEBUG, true);
                $this->logger->pushHandler($logHandler);
            }

            $this->runSyncTask($message->type, true);

            if (null !== $outputPath) {
                $this->logger->popHandler();
            }
        }
    }

    public function runSyncTask(string $type, bool $force = false): void
    {
        // Immediately halt if setup is not complete.
        $settings = $this->settingsRepo->readSettings();
        if (!$settings->isSetupComplete()) {
            $this->logger->notice(
                sprintf('Skipping sync task %s; setup not complete.', $type)
            );
            return;
        }

        $allSyncInfo = $this->getSyncTimes();

        if (!isset($allSyncInfo[$type])) {
            throw new InvalidArgumentException(sprintf('Invalid sync task: %s', $type));
        }

        $syncInfo = $allSyncInfo[$type];

        set_time_limit($syncInfo['timeout']);

        $this->logger->notice(
            sprintf('Running sync task: %s', $syncInfo['name']),
            [
                'force' => $force,
            ]
        );

        $lock = $this->lockFactory->createAndAcquireLock(
            resource: 'sync_' . $type,
            ttl: $syncInfo['timeout'],
            force: $force
        );

        if (false === $lock) {
            return;
        }

        try {
            $event = new GetSyncTasks($type);
            $this->eventDispatcher->dispatch($event);

            foreach ($event->getTasks() as $taskClass => $task) {
                try {
                    $lock->refresh($syncInfo['timeout']);
                } catch (Exception) {
                    // Noop
                }

                $this->logger->debug(
                    sprintf(
                        'Starting sub-task: %s',
                        $taskClass
                    )
                );

                $start_time = microtime(true);

                $task->run($force);

                $end_time = microtime(true);
                $time_diff = $end_time - $start_time;

                $this->logger->debug(
                    sprintf(
                        'Timer "%s" completed in %01.3f second(s).',
                        $taskClass,
                        round($time_diff, 3)
                    )
                );

                unset($task);
                $this->em->clear();

                gc_collect_cycles();
            }

            $settings = $this->settingsRepo->readSettings();
            $settings->updateSyncLastRunTime($type);
            $this->settingsRepo->writeSettings($settings);
        } finally {
            $lock->release();
        }

        $this->logger->debug(
            sprintf('Sync task "%s" completed successfully.', $syncInfo['name']),
        );
    }

    /**
     * @return mixed[]
     */
    public function getSyncTimes(): array
    {
        $shortTaskTimeout = $this->environment->getSyncShortExecutionTime();
        $longTaskTimeout = $this->environment->getSyncLongExecutionTime();

        $settings = $this->settingsRepo->readSettings();

        $syncs = [
            GetSyncTasks::SYNC_NOWPLAYING => [
                'name' => __('Now Playing Data'),
                'contents' => [
                    __('Now Playing Data'),
                ],
                'timeout' => $shortTaskTimeout,
                'latest' => $settings->getSyncNowplayingLastRun(),
                'interval' => 15,
            ],
            GetSyncTasks::SYNC_SHORT => [
                'name' => __('1-Minute Sync'),
                'contents' => [
                    __('Song Requests Queue'),
                ],
                'timeout' => $shortTaskTimeout,
                'latest' => $settings->getSyncShortLastRun(),
                'interval' => 60,
            ],
            GetSyncTasks::SYNC_MEDIUM => [
                'name' => __('5-Minute Sync'),
                'contents' => [
                    __('Check Media Folders'),
                ],
                'timeout' => $shortTaskTimeout,
                'latest' => $settings->getSyncMediumLastRun(),
                'interval' => 300,
            ],
            GetSyncTasks::SYNC_LONG => [
                'name' => __('1-Hour Sync'),
                'contents' => [
                    __('Analytics/Statistics'),
                    __('Cleanup'),
                ],
                'timeout' => $longTaskTimeout,
                'latest' => $settings->getSyncLongLastRun(),
                'interval' => 3600,
            ],
        ];

        foreach ($syncs as &$sync_info) {
            $sync_info['diff'] = time() - $sync_info['latest'];
        }

        return $syncs;
    }
}
