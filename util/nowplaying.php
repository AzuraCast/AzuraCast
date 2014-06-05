<?php
/**
 * Synchronization Script
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

if (DF_APPLICATION_ENV != 'production')
    \PVL\Debug::setEchoMode(TRUE);

set_time_limit(60);
ini_set('memory_limit', '256M');

// Prevent nowplaying from running on top of itself.
$last_start = \Entity\Settings::getSetting('nowplaying_last_started', 0);
$last_end = \Entity\Settings::getSetting('nowplaying_last_run', 0);

if ($last_start > $last_end && $last_start >= (time() - 60))
	exit;

// Sync schedules.
\Entity\Settings::setSetting('nowplaying_last_started', time());

\PVL\NowPlaying::generate();

\Entity\Settings::setSetting('nowplaying_last_run', time());