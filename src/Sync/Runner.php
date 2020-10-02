<?php
namespace App\Sync;

use App\Entity;
use App\Entity\Repository\SettingsRepository;
use App\Event\GetSyncTasks;
use App\EventDispatcher;
use App\Settings;
use Monolog\Logger;
use Symfony\Component\Lock\LockFactory;

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

    /**
     * Now-Playing Synchronization
     * The most frequent sync process, which must be optimized for speed,
     * as it runs approx. every 15 seconds.
     *
     * @param bool $force
     */
    public function syncNowplaying($force = false): void
    {
        $this->runSyncTask(GetSyncTasks::SYNC_NOWPLAYING, $force);
    }

    /**
     * Short Synchronization
     * This task runs automatically every minute.
     *
     * @param bool $force
     */
    public function syncShort($force = false): void
    {
        $this->runSyncTask(GetSyncTasks::SYNC_SHORT, $force);
    }

    /**
     * Medium Synchronization
     * This task runs automatically every 5 minutes.
     *
     * @param bool $force
     */
    public function syncMedium($force = false): void
    {
        $this->runSyncTask(GetSyncTasks::SYNC_MEDIUM, $force);
    }

    /**
     * Long Synchronization
     * This task runs automatically every hour.
     *
     * @param bool $force
     */
    public function syncLong($force = false): void
    {
        $this->runSyncTask(GetSyncTasks::SYNC_LONG, $force);
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

        $this->logger->notice(sprintf('Running sync task: %s', $syncInfo['name']));

        $lock = $this->lockFactory->createLock('sync_' . $type, $syncInfo['timeout']);

        if (!$lock->acquire($force)) {
            return;
        }

        try {
            $event = new GetSyncTasks($type);
            $this->eventDispatcher->dispatch($event);

            $tasks = $event->getTasks();

            foreach ($tasks as $taskClass => $task) {
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

    public function getSyncTimes(): array
    {
        $this->settingsRepo->clearCache();

        $syncs = [
            GetSyncTasks::SYNC_NOWPLAYING => [
                'name' => __('Now Playing Data'),
                'contents' => [
                    __('Now Playing Data'),
                ],
                'lastRunSetting' => Entity\Settings::NOWPLAYING_LAST_RUN,
                'timeout' => 600,
            ],
            GetSyncTasks::SYNC_SHORT => [
                'name' => __('1-Minute Sync'),
                'contents' => [
                    __('Song Requests Queue'),
                ],
                'lastRunSetting' => Entity\Settings::SHORT_SYNC_LAST_RUN,
                'timeout' => 600,
            ],
            GetSyncTasks::SYNC_MEDIUM => [
                'name' => __('5-Minute Sync'),
                'contents' => [
                    __('Check Media Folders'),
                ],
                'lastRunSetting' => Entity\Settings::MEDIUM_SYNC_LAST_RUN,
                'timeout' => 600,
            ],
            GetSyncTasks::SYNC_LONG => [
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
