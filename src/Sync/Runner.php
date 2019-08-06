<?php
namespace App\Sync;

use App\Entity;
use App\Entity\Repository\SettingsRepository;
use Monolog\Logger;
use Pimple\ServiceIterator;

/**
 * The runner of scheduled synchronization tasks.
 */
class Runner
{
    /** @var Logger */
    protected $logger;

    /** @var SettingsRepository */
    protected $settings;

    /** @var Task\AbstractTask[] */
    protected $tasks_nowplaying;

    /** @var Task\AbstractTask[] */
    protected $tasks_short;

    /** @var Task\AbstractTask[] */
    protected $tasks_medium;

    /** @var Task\AbstractTask[] */
    protected $tasks_long;

    public function __construct(
        SettingsRepository $settings,
        Logger $logger,
        array $tasks_nowplaying,
        array $tasks_short,
        array $tasks_medium,
        array $tasks_long)
    {
        $this->settings = $settings;
        $this->logger = $logger;

        $this->tasks_nowplaying = $tasks_nowplaying;
        $this->tasks_short = $tasks_short;
        $this->tasks_medium = $tasks_medium;
        $this->tasks_long = $tasks_long;
    }

    protected function _initSync($script_timeout = 60)
    {
        // Immediately halt if setup is not complete.
        if ($this->settings->getSetting(Entity\Settings::SETUP_COMPLETE, 0) == 0) {
            die('Setup not complete; halting synchronized task.');
        }

        set_time_limit($script_timeout);
        ini_set('memory_limit', '256M');

        if (APP_IS_COMMAND_LINE) {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
        }
    }

    /**
     * Now-Playing Synchronization
     * The most frequent sync process, which must be optimized for speed,
     * as it runs approx. every 15 seconds.
     *
     * @param bool $force
     */
    public function syncNowplaying($force = false)
    {
        $this->logger->info('Running Now Playing sync task');
        $this->_initSync(10);

        // Prevent nowplaying from running on top of itself.
        $last_start = $this->settings->getSetting(Entity\Settings::NOWPLAYING_LAST_STARTED, 0);
        $last_end = $this->settings->getSetting(Entity\Settings::NOWPLAYING_LAST_RUN, 0);

        if ($last_start > $last_end && $last_start >= (time() - 10) && !$force) {
            return;
        }

        $this->settings->setSetting(Entity\Settings::NOWPLAYING_LAST_STARTED, time());

        foreach($this->tasks_nowplaying as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\AbstractTask $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting(Entity\Settings::NOWPLAYING_LAST_RUN, time());
    }

    /**
     * Short Synchronization
     * This task runs automatically every minute.
     *
     * @param bool $force
     */
    public function syncShort($force = false)
    {
        $this->logger->info('Running 1-minute sync task');
        $this->_initSync(60);

        foreach($this->tasks_short as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\AbstractTask $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting(Entity\Settings::SHORT_SYNC_LAST_RUN, time());
    }

    /**
     * Medium Synchronization
     * This task runs automatically every 5 minutes.
     *
     * @param bool $force
     */
    public function syncMedium($force = false)
    {
        $this->logger->info('Running 5-minute sync task');
        $this->_initSync(300);

        foreach($this->tasks_medium as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\AbstractTask $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting(Entity\Settings::MEDIUM_SYNC_LAST_RUN, time());
    }

    /**
     * Long Synchronization
     * This task runs automatically every hour.
     *
     * @param bool $force
     */
    public function syncLong($force = false)
    {
        $this->logger->info('Running 1-hour sync task');
        $this->_initSync(1800);

        foreach($this->tasks_long as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\AbstractTask $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting(Entity\Settings::LONG_SYNC_LAST_RUN, time());
    }

    public function getSyncTimes()
    {
        $this->settings->clearCache();

        $syncs = [
            'nowplaying' => [
                'name' => __('Now Playing Data'),
                'latest' => $this->settings->getSetting(Entity\Settings::NOWPLAYING_LAST_RUN, 0),
                'contents' => [
                    __('Now Playing Data'),
                ],
            ],
            'short' => [
                'name' => __('1-Minute Sync'),
                'latest' => $this->settings->getSetting(Entity\Settings::SHORT_SYNC_LAST_RUN, 0),
                'contents' => [
                    __('Song Requests Queue'),
                ],
            ],
            'medium' => [
                'name' => __('5-Minute Sync'),
                'latest' => $this->settings->getSetting(Entity\Settings::MEDIUM_SYNC_LAST_RUN, 0),
                'contents' => [
                    __('Check Media Folders'),
                ],
            ],
            'long' => [
                'name' => __('1-Hour Sync'),
                'latest' => $this->settings->getSetting(Entity\Settings::LONG_SYNC_LAST_RUN, 0),
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

    protected function _runTimer($timer_description, callable $timed_function)
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
