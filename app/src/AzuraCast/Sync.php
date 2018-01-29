<?php
namespace AzuraCast;

use App\Debug;
use Doctrine\ORM\EntityManager;
use Entity\Settings;
use Entity\Repository\SettingsRepository;
use Interop\Container\ContainerInterface;

/**
 * The runner of scheduled synchronization tasks.
 *
 * Class Sync
 * @package App
 */
class Sync
{
    /**
     * @var ContainerInterface
     */
    protected $di;

    /**
     * @var SettingsRepository
     */
    protected $settings;

    public function __construct(ContainerInterface $di)
    {
        $this->di = $di;

        /** @var EntityManager $em */
        $em = $di[EntityManager::class];
        $this->settings = $em->getRepository(Settings::class);
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

        Debug::runTimer('Run NowPlaying update', function () {
            /** @var Sync\NowPlaying $task */
            $task = $this->di[Sync\NowPlaying::class];

            $task->run();
        });

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

        // Sync uploaded media.
        Debug::runTimer('Run radio station track sync', function () {
            /** @var Sync\Media $task */
            $task = $this->di[Sync\Media::class];
            $task->run();
        });

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

        // Sync analytical and statistical data (long running).
        Debug::runTimer('Run analytics manager', function () {
            /** @var Sync\Analytics $task */
            $task = $this->di[Sync\Analytics::class];
            $task->run();
        });

        // Run automated playlist assignment.
        Debug::runTimer('Run automated playlist assignment', function () {
            /** @var Sync\RadioAutomation $task */
            $task = $this->di[Sync\RadioAutomation::class];
            $task->run();
        });

        // Clean up old song history entries.
        Debug::runTimer('Run song history cleanup', function () {
            /** @var Sync\HistoryCleanup $task */
            $task = $this->di[Sync\HistoryCleanup::class];
            $task->run();
        });

        $this->settings->setSetting('sync_slow_last_run', time());
    }

    public function getSyncTimes()
    {
        $this->settings->clearCache();

        $syncs = [
            'nowplaying' => [
                'name' => _('Now Playing Data'),
                'latest' => $this->settings->getSetting('nowplaying_last_run', 0),
                'contents' => [
                    _('Now Playing Data'),
                ],
            ],
            'short' => [
                'name' => _('1-Minute Sync'),
                'latest' => $this->settings->getSetting('sync_fast_last_run', 0),
                'contents' => [
                    _('Song Requests Queue'),
                ],
            ],
            'medium' => [
                'name' => _('5-Minute Sync'),
                'latest' => $this->settings->getSetting('sync_last_run', 0),
                'contents' => [
                    _('Check Media Folders'),
                ],
            ],
            'long' => [
                'name' => _('1-Hour Sync'),
                'latest' => $this->settings->getSetting('sync_slow_last_run', 0),
                'contents' => [
                    _('Analytics/Statistics'),
                    _('Cleanup'),
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

}