<?php
/**
 * Synchronization Script (Runs every hour).
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

set_time_limit(1800);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

// Sync analytical and statistical data (long running).
\PVL\AnalyticsManager::run();

// Sync the BronyTunes library.
\PVL\Service\BronyTunes::load();

// Sync the Pony.fm library.
\PVL\Service\PonyFm::load();

// Sync the EqBeats library.
\PVL\Service\EqBeats::load();

\Entity\Settings::setSetting('sync_slow_last_run', time());