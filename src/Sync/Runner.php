<?php
namespace App\Sync;

use App\Entity;
use App\Entity\Repository\SettingsRepository;
use App\Lock\LockManager;
use App\Settings;
use Monolog\Logger;

/**
 * The runner of scheduled synchronization tasks.
 */
class Runner
{
    protected Logger $logger;

    protected SettingsRepository $settingsRepo;

    protected LockManager $lockManager;

    protected TaskLocator $taskCollection;

    public function __construct(
        SettingsRepository $settingsRepo,
        Logger $logger,
        LockManager $lockManager,
        TaskLocator $taskCollection
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->logger = $logger;
        $this->lockManager = $lockManager;
        $this->taskCollection = $taskCollection;
    }

    /**
     * Now-Playing Synchronization
     * The most frequent sync process, which must be optimized for speed,
     * as it runs approx. every 15 seconds.
     *
     * @param bool $force
     */
    public function syncNowplaying($force = false): void
    {
        $this->runSyncTask(TaskLocator::SYNC_NOWPLAYING);
    }

    /**
     * Short Synchronization
     * This task runs automatically every minute.
     *
     * @param bool $force
     */
    public function syncShort($force = false): void
    {
        $this->runSyncTask(TaskLocator::SYNC_SHORT);
    }

    /**
     * Medium Synchronization
     * This task runs automatically every 5 minutes.
     *
     * @param bool $force
     */
    public function syncMedium($force = false): void
    {
        $this->runSyncTask(TaskLocator::SYNC_MEDIUM);
    }

    /**
     * Long Synchronization
     * This task runs automatically every hour.
     *
     * @param bool $force
     */
    public function syncLong($force = false): void
    {
        $this->runSyncTask(TaskLocator::SYNC_LONG);
    }

    public function runSyncTask(string $type, bool $force = false): void
    {
        // Immediately halt if setup is not complete.
        if ($this->settingsRepo->getSetting(Entity\Settings::SETUP_COMPLETE, 0) == 0) {
            die('Setup not complete; halting synchronized task.');
        }

        $allSyncInfo = $this->getSyncTimes();
        $syncInfo = $allSyncInfo[$type];

        set_time_limit($syncInfo['timeout']);
        ini_set('memory_limit', '256M');

        if (Settings::getInstance()->isCli()) {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            ini_set('display_errors', '1');
            ini_set('log_errors', '1');
        }

        $this->logger->info(sprintf('Running sync task: %s', $syncInfo['name']));

        $lock = $this->lockManager->getLock('sync_' . $type, $syncInfo['timeout'], $force);

        $lock->run(function () use ($syncInfo, $type, $force) {
            $tasks = $this->taskCollection->getTasks($type);

            foreach ($tasks as $task) {
                // Filter namespace name
                $timer_description_parts = explode("\\", get_class($task));
                $timer_description = array_pop($timer_description_parts);

                $start_time = microtime(true);

                $task->run($force);

                $end_time = microtime(true);
                $time_diff = $end_time - $start_time;

                $this->logger->debug(sprintf(
                    'Timer "%s" completed in %01.3f second(s).',
                    $timer_description,
                    round($time_diff, 3)
                ));
            }

            $this->settingsRepo->setSetting($syncInfo['lastRunSetting'], time());
        });
    }

    public function getSyncTimes(): array
    {
        $this->settingsRepo->clearCache();

        $syncs = [
            TaskLocator::SYNC_NOWPLAYING => [
                'name' => __('Now Playing Data'),
                'contents' => [
                    __('Now Playing Data'),
                ],
                'lastRunSetting' => Entity\Settings::NOWPLAYING_LAST_RUN,
                'timeout' => 600,
            ],
            TaskLocator::SYNC_SHORT => [
                'name' => __('1-Minute Sync'),
                'contents' => [
                    __('Song Requests Queue'),
                ],
                'lastRunSetting' => Entity\Settings::SHORT_SYNC_LAST_RUN,
                'timeout' => 600,
            ],
            TaskLocator::SYNC_MEDIUM => [
                'name' => __('5-Minute Sync'),
                'contents' => [
                    __('Check Media Folders'),
                ],
                'lastRunSetting' => Entity\Settings::MEDIUM_SYNC_LAST_RUN,
                'timeout' => 600,
            ],
            TaskLocator::SYNC_LONG => [
                'name' => __('1-Hour Sync'),
                'contents' => [
                    __('Analytics/Statistics'),
                    __('Cleanup'),
                ],
                'lastRunSetting' => Entity\Settings::LONG_SYNC_LAST_RUN,
                'timeout' => 1800,
            ],
        ];

        foreach ($syncs as &$sync_info) {
            $sync_info['latest'] = $this->settingsRepo->getSetting($sync_info['lastRunSetting'], 0);
            $sync_info['diff'] = time() - $sync_info['latest'];
        }

        return $syncs;
    }
}
