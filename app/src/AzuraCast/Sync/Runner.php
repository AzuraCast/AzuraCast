<?php
namespace AzuraCast\Sync;

use Entity\Repository\SettingsRepository;
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

    /** @var ServiceIterator */
    protected $tasks_nowplaying;

    /** @var ServiceIterator */
    protected $tasks_short;

    /** @var ServiceIterator */
    protected $tasks_medium;

    /** @var ServiceIterator */
    protected $tasks_long;

    public function __construct(SettingsRepository $settings, Logger $logger, ServiceIterator $tasks_nowplaying,
                                ServiceIterator $tasks_short, ServiceIterator $tasks_medium, ServiceIterator $tasks_long)
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
        if ($this->settings->getSetting('setup_complete', 0) == 0) {
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
        $this->_initSync(10);

        // Prevent nowplaying from running on top of itself.
        $last_start = $this->settings->getSetting('nowplaying_last_started', 0);
        $last_end = $this->settings->getSetting('nowplaying_last_run', 0);

        if ($last_start > $last_end && $last_start >= (time() - 10) && !$force) {
            return;
        }

        $this->settings->setSetting('nowplaying_last_started', time());

        foreach($this->tasks_nowplaying as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\TaskAbstract $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting('nowplaying_last_run', time());
    }

    /**
     * Short Synchronization
     * This task runs automatically every minute.
     *
     * @param bool $force
     */
    public function syncShort($force = false)
    {
        $this->_initSync(60);

        foreach($this->tasks_short as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\TaskAbstract $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting('sync_fast_last_run', time());
    }

    /**
     * Medium Synchronization
     * This task runs automatically every 5 minutes.
     *
     * @param bool $force
     */
    public function syncMedium($force = false)
    {
        $this->_initSync(300);

        foreach($this->tasks_medium as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\TaskAbstract $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting('sync_last_run', time());
    }

    /**
     * Long Synchronization
     * This task runs automatically every hour.
     *
     * @param bool $force
     */
    public function syncLong($force = false)
    {
        $this->_initSync(1800);

        foreach($this->tasks_long as $task) {
            $this->_runTimer(get_class($task), function() use ($task, $force) {
                /** @var Task\TaskAbstract $task */
                $task->run($force);
            });
        }

        $this->settings->setSetting('sync_slow_last_run', time());
    }

    public function getSyncTimes()
    {
        $this->settings->clearCache();

        $syncs = [
            'nowplaying' => [
                'name' => __('Now Playing Data'),
                'latest' => $this->settings->getSetting('nowplaying_last_run', 0),
                'contents' => [
                    __('Now Playing Data'),
                ],
            ],
            'short' => [
                'name' => __('1-Minute Sync'),
                'latest' => $this->settings->getSetting('sync_fast_last_run', 0),
                'contents' => [
                    __('Song Requests Queue'),
                ],
            ],
            'medium' => [
                'name' => __('5-Minute Sync'),
                'latest' => $this->settings->getSetting('sync_last_run', 0),
                'contents' => [
                    __('Check Media Folders'),
                ],
            ],
            'long' => [
                'name' => __('1-Hour Sync'),
                'latest' => $this->settings->getSetting('sync_slow_last_run', 0),
                'contents' => [
                    __('Analytics/Statistics'),
                    __('Cleanup'),
                ],
            ],
        ];

        foreach ($syncs as $sync_key => $sync_info) {
            $sync_latest = $sync_info['latest'];

            $syncs[$sync_key]['diff'] = time() - $sync_latest;
            $syncs[$sync_key]['diff_text'] = \App\Utilities::timeDifferenceText($sync_latest, time());
        }

        return $syncs;
    }

    protected function _runTimer($timer_description, callable $timed_function)
    {
        $start_time = microtime(true);

        $timed_function();

        $end_time = microtime(true);
        $time_diff = $end_time - $start_time;

        $this->logger->debug('Timer "' . $timer_description . '" completed in ' . round($time_diff, 3) . ' second(s).');
    }
}