<?php
namespace App\Sync;

use Entity\Settings;
use Entity\SongHistory;
use App\Debug;

class Manager
{
    public static function syncNowplaying($force = false)
    {
        self::initSync(60);

        // Prevent nowplaying from running on top of itself.
        $last_start = Settings::getSetting('nowplaying_last_started', 0);
        $last_end = Settings::getSetting('nowplaying_last_run', 0);

        if ($last_start > $last_end && $last_start >= (time() - 10) && !$force)
            return;

        // Sync schedules.
        Settings::setSetting('nowplaying_last_started', time());

        // Run Now Playing data for radio streams.
        Debug::runTimer('Run NowPlaying update', function() {
            NowPlaying::sync();
        });

        Settings::setSetting('nowplaying_last_run', time());
    }

    public static function syncShort($force = false)
    {
        self::initSync(60);

        Settings::setSetting('sync_fast_last_run', time());
    }

    public static function syncMedium($force = false)
    {
        self::initSync(300);

        // Sync CentovaCast song data.
        Debug::runTimer('Run radio station track sync', function() {
            Media::sync();
        });

        Settings::setSetting('sync_last_run', time());
    }

    public static function syncLong($force = false)
    {
        self::initSync(1800);

        // Sync analytical and statistical data (long running).
        Debug::runTimer('Run analytics manager', function() {
            Analytics::sync();
        });

        // Clean up old song history entries.
        Debug::runTimer('Run song history cleanup', function() {
            SongHistory::cleanUp();
        });

        Settings::setSetting('sync_slow_last_run', time());
    }

    public static function getSyncTimes()
    {
        Settings::clearCache();

        $syncs = array(
            'nowplaying' => array(
                'name'      => 'Now Playing Data',
                'latest'    => Settings::getSetting('nowplaying_last_run', 0),
                'contents'  => array(
                    'Now Playing Data',
                ),
            ),
            /* 'short' => array(
                'name'      => '1-Minute Sync',
                'latest'    => Settings::getSetting('sync_fast_last_run', 0),
                'contents'  => array(
                    'Schedule Notifications',
                ),
            ), */
            'medium' => array(
                'name'      => '5-Minute Sync',
                'latest'    => Settings::getSetting('sync_last_run', 0),
                'contents'  => array(
                    'Media Folder Updates',
                ),
            ),
            'long' => array(
                'name'      => '1-Hour Sync',
                'latest'    => Settings::getSetting('sync_slow_last_run', 0),
                'contents'  => array(
                    'Analytics and Statistics',
                    'Song History Cleanup',
                ),
            ),
        );

        foreach($syncs as $sync_key => $sync_info)
        {
            $sync_latest = $sync_info['latest'];

            $syncs[$sync_key]['diff'] = time()-$sync_latest;
            $syncs[$sync_key]['diff_text'] = \App\Utilities::timeDifferenceText($sync_latest, time());
        }

        return $syncs;
    }

    public static function initSync($script_timeout = 60)
    {
        // Immediately halt if setup is not complete.
        if (Settings::getSetting('setup_complete', 0) == 0)
            die('Setup not complete; halting synchronized task.');

        set_time_limit($script_timeout);
        ini_set('memory_limit', '256M');

        if (APP_IS_COMMAND_LINE)
        {
            error_reporting(E_ALL & ~E_STRICT & ~E_NOTICE);
            ini_set('display_errors', 1);
            ini_set('log_errors', 1);
        }
    }
}