<?php
/**
 * Synchronization Script (Runs every 10 minutes).
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

set_time_limit(300);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

// Generate cache files.
\PVL\CacheManager::generateSlimPlayer();

// Sync schedules (highest priority).
\PVL\ScheduleManager::run();

// Sync show episodes and artist news (high priority).
\PVL\PodcastManager::run();

// Sync CentovaCast song data.
\PVL\CentovaCast::sync();

\Entity\Settings::setSetting('sync_last_run', time());