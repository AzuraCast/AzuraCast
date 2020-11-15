<?php

namespace App\Sync;

use App\Entity;
use App\Entity\Repository\SettingsRepository;
use App\Event\GetSyncTasks;
use App\EventDispatcher;
use App\LockFactory;
use App\Settings;
use Monolog\Logger;

/**
 * The runner of scheduled synchronization tasks.
 */
class Runner
{
    protected Logger $logger;

    protected SettingsRepository $settingsRepo;

    protected LockFactory $lockFactory;

    protected EventDispatcher $eventDispatcher;

    public function __construct(
        SettingsRepository $settingsRepo,
        Logger $logger,
        LockFactory $lockFactory,
        EventDispatcher $eventDispatcher
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->logger = $logger;
        $this->lockFactory = $lockFactory;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function runSyncTask(string $type, bool $force = false): void
    {
        // Immediately halt if setup is not complete.
        if ($this->settingsRepo->getSetting(Entity\Settings::SETUP_COMPLETE, 0) == 0) {
            die('Setup not complete; halting synchronized task.');
        }

        $allSyncInfo = $this->getSyncTimes();

        if (!isset($allSyncInfo[$type])) {
            throw new \InvalidArgumentException(sprintf('Invalid sync task: %s', $type));
        }

        $syncInfo = $allSyncInfo[$type];

        set_time_limit($syncInfo['timeout']);

        if (Settings::getInstance()->isCli()) {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            ini_set('display_errors', '1');
            ini_set('log_errors', '1');
        }

        $this->logger->notice(sprintf('Running sync task: %s', $syncInfo['name']));

        $lock = $this->lockFactory->createLock('sync_' . $type, $syncInfo['timeout']);

        if ($force) {
            $this->lockFactory->clearQueue('sync_' . $type);
            try {
                $lock->acquire($force);
            } catch (\Exception $e) {
                // Noop
            }
        } elseif (!$lock->acquire()) {
            return;
        }

        try {
            $event = new GetSyncTasks($type);
            $this->eventDispatcher->dispatch($event);

            $tasks = $event->getTasks();

            foreach ($tasks as $taskClass => $task) {
                if (!$lock->isAcquired()) {
                    return;
                }

                $start_time = microtime(true);

                $task->run($force);

                $end_time = microtime(true);
                $time_diff = $end_time - $start_time;

                $this->logger->debug(sprintf(
                    'Timer "%s" completed in %01.3f second(s).',
                    $taskClass,
                    round($time_diff, 3)
                ));
            }

            $this->settingsRepo->setSetting($syncInfo['lastRunSetting'], time());
        } finally {
            $lock->release();
        }
    }

    /**
     * @return mixed[]
     */
    public function getSyncTimes(): array
    {
        $this->settingsRepo->clearCache();

        $shortTaskTimeout = $_ENV['SYNC_SHORT_EXECUTION_TIME'] ?? 600;
        $longTaskTimeout = $_ENV['SYNC_LONG_EXECUTION_TIME'] ?? 1800;

        $syncs = [
            GetSyncTasks::SYNC_NOWPLAYING => [
                'name' => __('Now Playing Data'),
                'contents' => [
                    __('Now Playing Data'),
                ],
                'lastRunSetting' => Entity\Settings::NOWPLAYING_LAST_RUN,
                'timeout' => $shortTaskTimeout,
                'interval' => 15,
            ],
            GetSyncTasks::SYNC_SHORT => [
                'name' => __('1-Minute Sync'),
                'contents' => [
                    __('Song Requests Queue'),
                ],
                'lastRunSetting' => Entity\Settings::SHORT_SYNC_LAST_RUN,
                'timeout' => $shortTaskTimeout,
                'interval' => 60,
            ],
            GetSyncTasks::SYNC_MEDIUM => [
                'name' => __('5-Minute Sync'),
                'contents' => [
                    __('Check Media Folders'),
                ],
                'lastRunSetting' => Entity\Settings::MEDIUM_SYNC_LAST_RUN,
                'timeout' => $shortTaskTimeout,
                'interval' => 300,
            ],
            GetSyncTasks::SYNC_LONG => [
                'name' => __('1-Hour Sync'),
                'contents' => [
                    __('Analytics/Statistics'),
                    __('Cleanup'),
                ],
                'lastRunSetting' => Entity\Settings::LONG_SYNC_LAST_RUN,
                'timeout' => $longTaskTimeout,
                'interval' => 3600,
            ],
        ];

        foreach ($syncs as &$sync_info) {
            $sync_info['latest'] = $this->settingsRepo->getSetting($sync_info['lastRunSetting'], 0);
            $sync_info['diff'] = time() - $sync_info['latest'];
        }

        return $syncs;
    }
}
