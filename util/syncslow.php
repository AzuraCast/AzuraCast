<?php
/**
 * Synchronization Script (Runs every hour).
 */

require_once dirname(__FILE__) . '/../app/bootstrap.php';
$application->bootstrap();

\PVL\SyncManager::syncLong();