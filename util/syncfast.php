<?php
/**
 * Synchronization Script
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

set_time_limit(60);
error_reporting(E_ALL & ~E_NOTICE);
ini_set('display_errors', 1);
ini_set('memory_limit', '256M');

// Pull the homepage news.
\Entity\NetworkNews::load();

// Send notifications related to schedules (high priority).
\PVL\NotificationManager::run();

\Entity\Settings::setSetting('sync_fast_last_run', time());