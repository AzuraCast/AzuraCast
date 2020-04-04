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

    /** @var Task\AbstractTask[] */
    protected array $tasks_nowplaying;

    /** @var Task\AbstractTask[] */
    protected array $tasks_short;

    /** @var Task\AbstractTask[] */
    protected array $tasks_medium;

    /** @var Task\AbstractTask[] */
    protected array $tasks_long;

    public function __construct(
        SettingsRepository $settingsRepo,
        Logger $logger,
        LockManager $lockManager,
        array $tasks_nowplaying,
        array $tasks_short,
        array $tasks_medium,
        array $tasks_long
    ) {
        $this->settingsRepo = $settingsRepo;
        $this->logger = $logger;
        $this->lockManager = $lockManager;

        $this->tasks_nowplaying = $tasks_nowplaying;
        $this->tasks_short = $tasks_short;
        $this->tasks_medium = $tasks_medium;
        $this->tasks_long = $tasks_long;
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
        $this->logger->info('Running Now Playing sync task');
        $this->initSync(600);

        $lock = $this->lockManager->getLock('sync_nowplaying', 600);

        $lock->run(function () use ($force) {
            foreach ($this->tasks_nowplaying as $task) {
                $this->runTimer(get_class($task), function () use ($task, $force) {
                    /** @var Task\AbstractTask $task */
                    $task->run($force);
                });
            }

            $this->settingsRepo->setSetting(Entity\Settings::NOWPLAYING_LAST_RUN, time());
        }, $force);
    }

    /**
     * Short Synchronization
     * This task runs automatically every minute.
     *
     * @param bool $force
     */
    public function syncShort($force = false): void
    {
        $this->logger->info('Running 1-minute sync task');
        $this->initSync(600);

        $lock = $this->lockManager->getLock('sync_short', 600);

        $lock->run(function () use ($force) {
            foreach ($this->tasks_short as $task) {
                $this->runTimer(get_class($task), function () use ($task, $force) {
                    /** @var Task\AbstractTask $task */
                    $task->run($force);
                });
            }

            $this->settingsRepo->setSetting(Entity\Settings::SHORT_SYNC_LAST_RUN, time());
        }, $force);
    }

    /**
     * Medium Synchronization
     * This task runs automatically every 5 minutes.
     *
     * @param bool $force
     */
    public function syncMedium($force = false): void
    {
        $this->logger->info('Running 5-minute sync task');
        $this->initSync(600);

        $lock = $this->lockManager->getLock('sync_medium', 600);

        $lock->run(function () use ($force) {
            foreach ($this->tasks_medium as $task) {
                $this->runTimer(get_class($task), function () use ($task, $force) {
                    /** @var Task\AbstractTask $task */
                    $task->run($force);
                });
            }

            $this->settingsRepo->setSetting(Entity\Settings::MEDIUM_SYNC_LAST_RUN, time());
        }, $force);
    }

    /**
     * Long Synchronization
     * This task runs automatically every hour.
     *
     * @param bool $force
     */
    public function syncLong($force = false): void
    {
        $this->logger->info('Running 1-hour sync task');
        $this->initSync(1800);

        $lock = $this->lockManager->getLock('sync_medium', 1800);

        $lock->run(function () use ($force) {
            foreach ($this->tasks_long as $task) {
                $this->runTimer(get_class($task), function () use ($task, $force) {
                    /** @var Task\AbstractTask $task */
                    $task->run($force);
                });
            }

            $this->settingsRepo->setSetting(Entity\Settings::LONG_SYNC_LAST_RUN, time());
        }, $force);
    }

    public function getSyncTimes(): array
    {
        $this->settingsRepo->clearCache();

        $syncs = [
            'nowplaying' => [
                'name' => __('Now Playing Data'),
                'latest' => $this->settingsRepo->getSetting(Entity\Settings::NOWPLAYING_LAST_RUN, 0),
                'contents' => [
                    __('Now Playing Data'),
                ],
            ],
            'short' => [
                'name' => __('1-Minute Sync'),
                'latest' => $this->settingsRepo->getSetting(Entity\Settings::SHORT_SYNC_LAST_RUN, 0),
                'contents' => [
                    __('Song Requests Queue'),
                ],
            ],
            'medium' => [
                'name' => __('5-Minute Sync'),
                'latest' => $this->settingsRepo->getSetting(Entity\Settings::MEDIUM_SYNC_LAST_RUN, 0),
                'contents' => [
                    __('Check Media Folders'),
                ],
            ],
            'long' => [
                'name' => __('1-Hour Sync'),
                'latest' => $this->settingsRepo->getSetting(Entity\Settings::LONG_SYNC_LAST_RUN, 0),
                'contents' => [
                    __('Analytics/Statistics'),
                    __('Cleanup'),
                ],
            ],
        ];

        foreach ($syncs as &$sync_info) {
            $sync_info['diff'] = time() - $sync_info['latest'];
        }

        return $syncs;
    }

    protected function initSync($script_timeout = 60): void
    {
        // Immediately halt if setup is not complete.
        if ($this->settingsRepo->getSetting(Entity\Settings::SETUP_COMPLETE, 0) == 0) {
            die('Setup not complete; halting synchronized task.');
        }

        set_time_limit($script_timeout);
        ini_set('memory_limit', '256M');

        if (Settings::getInstance()->isCli()) {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
        }
    }

    protected function runTimer($timer_description, callable $timed_function): void
    {
        // Filter namespace name
        $timer_description_parts = explode("\\", $timer_description);
        $timer_description = array_pop($timer_description_parts);

        $start_time = microtime(true);

        $timed_function();

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $this->logger->debug('Timer "' . $timer_description . '" completed in ' . round($time_diff, 3) . ' second(s).');
    }
}
